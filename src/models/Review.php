<?php

class Review {
    private ?int $id;
    private int $userId;
    private int $gameId;
    private ?string $username;
    private $avatar;
    private int $rating;
    private string $content;
    private bool $isEdited;
    private ?string $createdAt;

    private int $likesCount;
    private bool $isLikedByCurrentUser;

    public function __construct(?int $id, int $userId, int $gameId, ?string $username, $avatar, int $rating, string $content, bool $isEdited, ?string $createdAt, int $likesCount, bool $isLikedByCurrentUser) {
        $this->id = $id;
        $this->userId = $userId;
        $this->gameId = $gameId;
        $this->username = $username;
        $this->avatar = $avatar;
        $this->rating = $rating;
        $this->content = $content;
        $this->isEdited = $isEdited;
        $this->createdAt = $createdAt;
        $this->likesCount = $likesCount;
        $this->isLikedByCurrentUser = $isLikedByCurrentUser;
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getGameId(): int { return $this->gameId; }
    public function getUsername(): ?string { return $this->username; }
    public function getAvatar() { return $this->avatar; }
    public function getRating(): int { return $this->rating; }
    public function getContent(): string { return $this->content; }
    public function isEdited(): bool { return $this->isEdited; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getLikesCount(): int { return $this->likesCount; }
    public function isLikedByCurrentUser(): bool { return $this->isLikedByCurrentUser; }
}