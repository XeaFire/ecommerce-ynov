<?php

namespace App\Entity;

use App\Repository\MasterclassRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MasterclassRepository::class)]
class Masterclass
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit faire au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être positif")]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'masterclasses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\OneToMany(mappedBy: 'masterclass', targetEntity: MasterclassPage::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $pages;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'purchasedMasterclasses')]
    private Collection $students;

    #[ORM\OneToMany(mappedBy: 'masterclass', targetEntity: MasterclassProgress::class, orphanRemoval: true)]
    private Collection $progressRecords;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->pages = new ArrayCollection();
        $this->students = new ArrayCollection();
        $this->progressRecords = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Collection<int, MasterclassPage>
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(MasterclassPage $page): static
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
            $page->setMasterclass($this);
        }

        return $this;
    }

    public function removePage(MasterclassPage $page): static
    {
        if ($this->pages->removeElement($page)) {
            // set the owning side to null (unless already changed)
            if ($page->getMasterclass() === $this) {
                $page->setMasterclass(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(User $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
        }

        return $this;
    }

    public function removeStudent(User $student): static
    {
        $this->students->removeElement($student);

        return $this;
    }

    public function hasStudent(User $user): bool
    {
        return $this->students->contains($user);
    }

    /**
     * @return Collection<int, MasterclassProgress>
     */
    public function getProgressRecords(): Collection
    {
        return $this->progressRecords;
    }

    public function addProgressRecord(MasterclassProgress $progressRecord): static
    {
        if (!$this->progressRecords->contains($progressRecord)) {
            $this->progressRecords->add($progressRecord);
            $progressRecord->setMasterclass($this);
        }

        return $this;
    }

    public function removeProgressRecord(MasterclassProgress $progressRecord): static
    {
        if ($this->progressRecords->removeElement($progressRecord)) {
            // set the owning side to null (unless already changed)
            if ($progressRecord->getMasterclass() === $this) {
                $progressRecord->setMasterclass(null);
            }
        }

        return $this;
    }

    public function getProgressForUser(User $user): ?MasterclassProgress
    {
        foreach ($this->progressRecords as $progress) {
            if ($progress->getUser() === $user) {
                return $progress;
            }
        }
        
        return null;
    }
}
