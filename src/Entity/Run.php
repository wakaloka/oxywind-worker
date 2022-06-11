<?php

namespace App\Entity;

use App\Entity\Enum\RunStatus;
use App\Repository\RunRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RunRepository::class)]
class Run
{
    #[Groups(['run:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'bigint')]
    private $id;

    #[Groups(['run:read'])]
    #[ORM\Column(type: 'json')]
    private $job = [];

    #[Groups(['run:read'])]
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[Groups(['run:read'])]
    #[ORM\Column(type: 'string', length: 255, enumType: RunStatus::class, options: ['default' => RunStatus::Completed])]
    private $status;

    public function __construct()
    {
        $this->status = RunStatus::Completed;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getJob(): ?array
    {
        return $this->job;
    }

    public function setJob(array $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): ?RunStatus
    {
        return $this->status;
    }

    public function setStatus(string|RunStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
