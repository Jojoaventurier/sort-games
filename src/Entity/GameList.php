<?php
// src/Entity/GameList.php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GameList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:255)]
    private string $name;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $source = null; // optional: HD or folder name

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $uploadedAt;

    #[ORM\OneToMany(mappedBy:"gameList", targetEntity:GameEntry::class, cascade:["persist","remove"], orphanRemoval: true)]
    private Collection $games;

    public function __construct()
    {
        $this->games = new ArrayCollection();
    }

    // --- getters and setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }
    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getUploadedAt(): \DateTimeInterface
    {
        return $this->uploadedAt;
    }
    public function setUploadedAt(\DateTimeInterface $uploadedAt): self
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }

    /** @return Collection|GameEntry[] */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(GameEntry $game): self
    {
        if (!$this->games->contains($game)) {
            $this->games[] = $game;
            $game->setGameList($this);
        }
        return $this;
    }

    public function removeGame(GameEntry $game): self
    {
        if ($this->games->removeElement($game)) {
            if ($game->getGameList() === $this) {
                $game->setGameList(null);
            }
        }
        return $this;
    }
}
