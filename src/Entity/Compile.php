<?php

namespace App\Entity;

use App\Entity\Enum\Status;
use App\Repository\CompileRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CompileRepository::class)]
class Compile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['compile:read'])]
    #[Assert\Uuid]
    #[ORM\Column(type: 'uuid')]
    private $uuid;

    #[Groups(['compile:read'])]
    #[ORM\Column(type: 'string', length: 255, enumType: Status::class, options: ['default' => Status::Pending])]
    private $status;

    #[Groups(['compile:read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $package;

    #[Groups(['compile:read'])]
    #[Assert\NotBlank(message: 'Tailwind config field is required.')]
    #[ORM\Column(type: 'text')]
    private $config;
    
    #[Groups(['compile:read'])]
    #[Assert\NotBlank(message: 'Tailwind version field is required.')]
    #[ORM\Column(type: 'string', length: 255)]
    private $version;
    
    #[Groups(['compile:read'])]
    #[Assert\NotBlank(message: 'The global css field is required.')]
    #[ORM\Column(type: 'text', nullable: true)]
    private $css;
    
    #[Groups(['compile:read'])]
    #[Assert\NotBlank(message: 'WordPress Nonce field is required.')]
    #[ORM\Column(type: 'string', length: 255)]
    private $nonce;
    
    #[Groups(['compile:read'])]
    #[Assert\NotBlank(message: 'The WordPress URL field is required.')]
    #[Assert\Url(protocols: ['http', 'https'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $site;
    
    #[Groups(['compile:read'])]
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    private $createdAt;
    
    #[Groups(['compile:read'])]
    #[Assert\NotBlank(message: 'The content field is required.')]
    #[ORM\Column(type: 'text', nullable: true)]
    private $content;

    #[Groups(['compile:read'])]
    #[ORM\OneToOne(targetEntity: Run::class, cascade: ['persist', 'remove'])]
    private $run;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->status = Status::Pending;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(string|Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPackage(): ?string
    {
        return $this->package;
    }

    public function setPackage(?string $package): self
    {
        $this->package = $package;

        return $this;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function setConfig(string $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getCss(): ?string
    {
        return $this->css;
    }

    public function setCss(?string $css): self
    {
        $this->css = $css;

        return $this;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function setNonce(string $nonce): self
    {
        $this->nonce = $nonce;

        return $this;
    }

    public function getSite(): ?string
    {
        return $this->site;
    }

    public function setSite(string $site): self
    {
        $this->site = $site;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getRun(): ?Run
    {
        return $this->run;
    }

    public function setRun(?Run $run): self
    {
        $this->run = $run;

        return $this;
    }
}
