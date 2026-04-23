<?php

 // Represents a single game entity from the store
class Game {
    private int $id;
    private string $title;
    private string $description;
    private string $category;
    private float $price;
    private string $graphics;
    private float $averageRating;
    private ?string $specification;
    private string $developer;
    private string $releaseDate;
    private ?string $gameFile;

    // Game constructor
    public function __construct(int $id, string $title, string $description, string $category, float $price, string $graphics, float $averageRating, ?string $specification, string $developer, string $releaseDate, ?string $gameFile) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->category = $category;
        $this->price = $price;
        $this->graphics = $graphics;
        $this->averageRating = $averageRating;
        $this->specification = $specification;
        $this->developer = $developer;
        $this->releaseDate = $releaseDate;
        $this->gameFile = $gameFile;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getCategory(): string { return $this->category; }
    public function getPrice(): float { return $this->price; }
    public function getGraphics(): string { return $this->graphics; }
    public function getAverageRating(): float { return $this->averageRating; }
    public function getSpecification(): ?string { return $this->specification; }
    public function getDeveloper(): string { return $this->developer; }
    public function getReleaseDate(): string { return $this->releaseDate; }
    public function getGameFile(): ?string { return $this->gameFile; }

    // Helpers

    // Checks if the game is free
    public function isFree(): bool { 
        return $this->price <= 0; 
    }

    // Checks if the game has been released
    public function isReleased(): bool { 
        return strtotime($this->releaseDate) <= strtotime('today'); 
    }

    // Returns the formatted price (e.g., "Free" or "199.99 PLN")
    public function getFormattedPrice(): string { 
        return $this->isFree() ? 'Free' : number_format($this->price, 2) . ' PLN'; 
    }

    // Checks if the game has an associated game file (indicating it's a real game, not just a placeholder)
    public function isRealGame(): bool { 
        return !empty($this->gameFile); 
    }
}