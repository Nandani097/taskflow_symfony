<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $event_type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $event_message = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

// task can be nullable — handles edge cases gracefully
#[ORM\ManyToOne(inversedBy: 'activityLogs')]
#[ORM\JoinColumn(nullable: true)]
private ?Task $task = null;

#[ORM\ManyToOne(inversedBy: 'activityLogs')]
#[ORM\JoinColumn(nullable: true)]
private ?User $user = null;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventType(): ?string
    {
        return $this->event_type;
    }
    public function setEventType(string $event_type): static
    {
        $this->event_type = $event_type;
        return $this;
    }

    public function getEventMessage(): ?string
    {
        return $this->event_message;
    }
    public function setEventMessage(string $event_message): static
    {
        $this->event_message = $event_message;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }
    public function setTask(?Task $task): static
    {
        $this->task = $task;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
