<?php

class Review {
    private ?int $id;
    private int $userId;
    private int $gameId;
    private int $rating;
    private string $content;
    private ?string $createdAt;
    private ?string $username;

    public function __construct(int $userId, int $gameId, int $rating, string $content, ?int $id = null, ?string $createdAt = null, ?string $username = null) {
        $this->userId = $userId;
        $this->gameId = $gameId;
        $this->rating = $rating;
        $this->content = $content;
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->username = $username;
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getGameId(): int { return $this->gameId; }
    public function getRating(): int { return $this->rating; }
    public function getContent(): string { return $this->content; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUsername(): ?string { return $this->username; }
}