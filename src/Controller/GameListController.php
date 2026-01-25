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

    public function __construct(protected GameMatcher $matcher, protected GameEntryRepository $gameEntryRepository) 
    {

    }

    #[Route('/game-lists/upload', name: 'game_list_upload')]
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(GameListUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            $listName = $form->get('name')->getData();

            $gameList = new GameList();
            $gameList->setName($listName);
            $gameList->setUploadedAt(new \DateTime());

            $em->persist($gameList);
            $em->flush(); // so we have an ID for relations

            // Read file lines
            $lines = file($uploadedFile->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '|') !== false) {
                    [$name, $tag] = array_map('trim', explode('|', $line, 2));

                    $entry = new GameEntry();
                    $entry->setGameList($gameList);
                    $entry->setName($name);
                    $entry->setTag($tag);
                    $entry->setCreatedAt(new \DateTime());
                    $normalized = $this->matcher->normalize($name);
                    $fuzzy = $this->matcher->fuzzy($normalized);

                    $entry->setNormalizedName($normalized);
                    $entry->setFuzzyName($fuzzy);

                    $em->persist($entry);
                }
            }

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
            'lists' => $lists
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

    // #[Route('/collections/duplicates', name: 'game_list_duplicates')]
    // public function duplicates(GameListRepository $repo, GameMatcher $matcher)
    // {
    //     $allLists = $repo->findAll();
    //     $duplicateMap = [];

    //     foreach ($allLists as $list) {
    //         foreach ($list->getGames() as $originalName) {
    //             $normalized = $matcher->normalize($originalName);
                
    //             $duplicateMap[$normalized][] = [
    //                 'original' => $originalName,
    //                 'listName' => $list->getName(),
    //                 'listId' => $list->getId()
    //             ];
    //         }
    //     }

    //     // Filter to keep only those that appear more than once
    //     $duplicates = array_filter($duplicateMap, fn($occurences) => count($occurences) > 1);

    //     return $this->render('game_list/duplicates.html.twig', [
    //         'duplicates' => $duplicates
    //     ]);
    // }

    #[Route('/duplicates', name: 'game_duplicates')]
    public function duplicates(Request $request): Response
    {
        $listIds = $request->query->all('lists');

        if (!$listIds) {
            return $this->render('game_list/duplicates.html.twig', [
                'duplicates' => [],
            ]);
        }

        $duplicates = $this->gameEntryRepository->findDuplicatesInLists($listIds);

        return $this->render('game_list/duplicates.html.twig', [
            'duplicates' => $duplicates,
            'listIds' => $listIds,
        ]);
    }

    #[Route('/search', name: 'game_search')]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q');
        $listIds = $request->query->all('lists');

        $results = [];
        if ($query && $listIds) {
            $results = $this->gameEntryRepository->searchInLists($query, $listIds);
        }

        return $this->render('game_list/search.html.twig', [
            'results' => $results,
        ]);
    }
}
