<?php

namespace App\Controller;

use App\Entity\GameList;
use App\Entity\GameEntry;
use App\Form\GameListUploadType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\GameMatcher;
use App\Repository\GameListRepository;
use App\Repository\GameEntryRepository;

class GameListController extends AbstractController
{

    public function __construct(protected GameMatcher $matcher) 
    {

    }

    #[Route('/game-lists/matrix', name: 'game_list_matrix')]
    public function matrix(GameListRepository $listRepo, GameEntryRepository $entryRepo): Response
    {
        // 1. Get the 6 most recent lists (Hard Drives)
        $lists = $listRepo->findBy([], ['uploadedAt' => 'DESC'], 6);
        
        // 2. Get all entries for these lists
        $listIds = array_map(fn($l) => $l->getId(), $lists);
        $entries = $entryRepo->findBy(['gameList' => $listIds]);

        // 3. Build the Matrix: [NormalizedName][ListID] = Entry
        $matrix = [];
        foreach ($entries as $entry) {
            $norm = $entry->getNormalizedName();
            $matrix[$norm][$entry->getGameList()->getId()] = [
                'name' => $entry->getName(),
                'size' => $entry->getFileSize(),
                'tag'  => $entry->getTag()
            ];
        }

        // 4. Filter only duplicates (where a row has > 1 list entry)
        $duplicatesOnly = array_filter($matrix, fn($row) => count($row) > 1);
        
        // Sort alphabetically
        ksort($duplicatesOnly);

        return $this->render('game_list/matrix.html.twig', [
            'lists' => $lists,
            'matrix' => $duplicatesOnly,
        ]);
    }

    #[Route('/game-lists/library', name: 'game_library_overview')]
    public function library(GameEntryRepository $entryRepo, GameListRepository $listRepo): Response
    {
        $allEntries = $entryRepo->findAll();
        $library = [];

        foreach ($allEntries as $entry) {
            $groupKey = $entry->getFuzzyName() ?: 'Unknown';
            $library[$groupKey][] = $entry;
        }

        // Sort the groups alphabetically by game title
        ksort($library);

        // Sort entries inside each group by date (Newest first)
        foreach ($library as $title => &$entries) {
            usort($entries, function($a, $b) {
                return strcmp($b->getFileDate(), $a->getFileDate());
            });
        }

        return $this->render('game_list/library.html.twig', [
            'library' => $library,
            'allLists' => $listRepo->findAll(), // For the filter dropdown
        ]);
    }

    #[Route('/game-lists/upload', name: 'game_list_upload')]
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(GameListUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            $listName = $form->get('name')->getData();

            // 1. Create the Parent List
            $gameList = new GameList();
            $gameList->setName($listName);
            $gameList->setUploadedAt(new \DateTime());

            $em->persist($gameList);
            $em->flush(); 

            // 2. Read File
            $lines = file($uploadedFile->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Remove potential UTF-8 BOM or hidden characters
                $line = str_replace(["\xEF\xBB\xBF", "\u{FEFF}"], '', $line);
                
                // Based on your dump, the line splits into 4 parts because of the internal |
                $parts = explode('|', $line);
                
                if (count($parts) >= 4) {
                    $name = trim($parts[0]);
                    $tag  = trim($parts[1]);
                    
                    // trim($parts[2], " []") removes spaces and the opening bracket [
                    // trim($parts[3], " []") removes spaces and the closing bracket ]
                    $fileDate = trim($parts[2], " []");
                    $fileSize = trim($parts[3], " []");

                    // --- DEBUG START ---
                    // Remove these 5 lines once you verify the output in your browser
                    // dd([
                    //     'step' => 'Verifying metadata extraction',
                    //     'raw_parts' => $parts,
                    //     'extracted_name' => $name,
                    //     'extracted_date' => $fileDate,
                    //     'extracted_size' => $fileSize
                    // ]);
                    // --- DEBUG END ---

                    $entry = new GameEntry();
                    $entry->setGameList($gameList);
                    $entry->setName($name);
                    $entry->setTag($tag);
                    $entry->setFileDate($fileDate);
                    $entry->setFileSize($fileSize);
                    $entry->setCreatedAt(new \DateTime());

                    // 4. Matcher Logic
                    $normalized = $this->matcher->normalize($name);
                    $entry->setNormalizedName($normalized);
                    $entry->setFuzzyName($this->matcher->fuzzy($normalized));

                    $em->persist($entry);
                }
            }

            // 5. Final Save
            $em->flush();

            $this->addFlash('success', 'List uploaded and saved!');
            return $this->redirectToRoute('game_list_index');
        }

        return $this->render('game_list/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/game-lists', name: 'game_list_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $lists = $em->getRepository(GameList::class)->findBy([], ['uploadedAt' => 'DESC']);

        return $this->render('game_list/index.html.twig', [
            'lists' => $lists,
            // Default values for the script generator
            'defaultDrive' => 'D',
            'defaultOutput' => 'C:\Games\my_collection.txt'
        ]);
    }

    #[Route('/game-lists/{id}', name: 'game_list_show')]
    public function show(GameList $gameList): Response
    {
        $entries = $gameList->getGames();

        return $this->render('game_list/show.html.twig', [
            'gameList' => $gameList,
            'entries' => $entries
        ]);
    }

    #[Route('/game-lists/{id}/delete', name: 'game_list_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        GameList $gameList,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete_game_list_' . $gameList->getId(),
            $request->request->get('_token')
        )) {
            $em->remove($gameList);
            $em->flush();

            $this->addFlash('success', 'Game list deleted.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('game_list_index');
    }

    #[Route('/collections/duplicates', name: 'game_list_duplicates')]
    public function duplicates(GameListRepository $repo, GameMatcher $matcher)
    {
        $allLists = $repo->findAll();
        $duplicateMap = [];

        foreach ($allLists as $list) {
            foreach ($list->getGames() as $originalName) {
                $normalized = $matcher->normalize($originalName);
                
                $duplicateMap[$normalized][] = [
                    'original' => $originalName,
                    'listName' => $list->getName(),
                    'listId' => $list->getId()
                ];
            }
        }

        // Filter to keep only those that appear more than once
        $duplicates = array_filter($duplicateMap, fn($occurences) => count($occurences) > 1);

        return $this->render('game_list/duplicates.html.twig', [
            'duplicates' => $duplicates
        ]);
    }
}
