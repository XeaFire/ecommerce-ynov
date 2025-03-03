<?php

namespace App\Entity;

use App\Repository\MasterclassProgressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MasterclassProgressRepository::class)]
class MasterclassProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'progressRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'progressRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Masterclass $masterclass = null;

    #[ORM\Column]
    private array $completedPages = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastActivityAt = null;

    #[ORM\Column]
    private ?int $progressPercentage = 0;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
        $this->lastActivityAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMasterclass(): ?Masterclass
    {
        return $this->masterclass;
    }

    public function setMasterclass(?Masterclass $masterclass): static
    {
        $this->masterclass = $masterclass;

        return $this;
    }

    public function getCompletedPages(): array
    {
        return $this->completedPages;
    }

    public function setCompletedPages(array $completedPages): static
    {
        $this->completedPages = $completedPages;

        return $this;
    }

    public function addCompletedPage(int $pageId): static
    {
        if (!in_array($pageId, $this->completedPages)) {
            $this->completedPages[] = $pageId;
            $this->updateProgressPercentage();
            $this->lastActivityAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function removeCompletedPage(int $pageId): static
    {
        if (($key = array_search($pageId, $this->completedPages)) !== false) {
            unset($this->completedPages[$key]);
            $this->completedPages = array_values($this->completedPages); // Reindex array
            $this->updateProgressPercentage();
            $this->lastActivityAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isPageCompleted(int $pageId): bool
    {
        return in_array($pageId, $this->completedPages);
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getLastActivityAt(): ?\DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeImmutable $lastActivityAt): static
    {
        $this->lastActivityAt = $lastActivityAt;

        return $this;
    }

    public function getProgressPercentage(): ?int
    {
        return $this->progressPercentage;
    }

    public function setProgressPercentage(int $progressPercentage): static
    {
        $this->progressPercentage = $progressPercentage;

        return $this;
    }

    private function updateProgressPercentage(): void
    {
        if ($this->masterclass && count($this->masterclass->getPages()) > 0) {
            $totalPages = count($this->masterclass->getPages());
            $completedPages = count($this->completedPages);
            $this->progressPercentage = (int) (($completedPages / $totalPages) * 100);
        } else {
            $this->progressPercentage = 0;
        }
    }
}
