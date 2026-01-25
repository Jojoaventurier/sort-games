<?php
// src/Entity/GameEntry.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\GameEntryRepository;

#[ORM\Entity(repositoryClass: GameEntryRepository::class)]
class GameEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity:GameList::class, inversedBy:"games")]
    #[ORM\JoinColumn(nullable:false)]
    private ?GameList $gameList = null;

    #[ORM\Column(type:"string", length:255)]
    private string $name; // raw name from file

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $normalizedName;

    #[ORM\Column(length: 255)]
    private string $fuzzyName;


    #[ORM\Column(type:"string", length:50)]
    private string $tag; // ISO, RAR, ZIP, FOLDER

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $version = null; // optional: parsed later

    // --- getters and setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGameList(): ?GameList
    {
        return $this->gameList;
    }
    public function setGameList(?GameList $gameList): self
    {
        $this->gameList = $gameList;
        return $this;
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

    public function getTag(): string
    {
        return $this->tag;
    }
    public function setTag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }
    public function setVersion(?string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getNormalizedName(): ?string
    {
        return $this->normalizedName;
    }
    public function setNormalizedName(?string $normalizedName): self
    {
        $this->normalizedName = $normalizedName;
        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Get the value of fuzzyName
     */ 
    public function getFuzzyName()
    {
        return $this->fuzzyName;
    }

    /**
     * Set the value of fuzzyName
     *
     * @return  self
     */ 
    public function setFuzzyName($fuzzyName)
    {
        $this->fuzzyName = $fuzzyName;

        return $this;
    }
}
