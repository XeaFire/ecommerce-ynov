<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
#[UniqueEntity(fields: ['username'], message: 'Ce pseudo est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email n'est pas valide")]
    private ?string $email = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "Le pseudo est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 25,
        minMessage: "Le pseudo doit faire au moins {{ limit }} caractères",
        maxMessage: "Le pseudo ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: "float", options: ["default" => 0.0])]
    private float $balance = 0.0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private bool $isCertified = false;

    #[ORM\OneToMany(mappedBy: 'seller', targetEntity: Article::class, orphanRemoval: true)]
    private Collection $articles;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Cart::class, cascade: ['persist', 'remove'])]
    private ?Cart $cart = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class, orphanRemoval: true)]
    private Collection $orders;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Masterclass::class, orphanRemoval: true)]
    private Collection $masterclasses;

    #[ORM\ManyToMany(targetEntity: Masterclass::class, mappedBy: 'students')]
    private Collection $purchasedMasterclasses;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: MasterclassProgress::class, orphanRemoval: true)]
    private Collection $masterclassProgress;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->articles = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->masterclasses = new ArrayCollection();
        $this->purchasedMasterclasses = new ArrayCollection();
        $this->masterclassProgress = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): static
    {
        $this->balance = $balance;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getIsCertified(): bool
    {
        return $this->isCertified;
    }

    public function setIsCertified(bool $isCertified): static
    {
        $this->isCertified = $isCertified;
        
        // Ajouter ou retirer le rôle ROLE_CERTIFIED en fonction du statut de certification
        $roles = $this->getRoles();
        
        if ($isCertified) {
            // Ajouter le rôle ROLE_CERTIFIED s'il n'existe pas déjà
            if (!in_array('ROLE_CERTIFIED', $roles)) {
                $roles[] = 'ROLE_CERTIFIED';
                $this->setRoles($roles);
            }
        } else {
            // Retirer le rôle ROLE_CERTIFIED s'il existe
            if (in_array('ROLE_CERTIFIED', $roles)) {
                $roles = array_diff($roles, ['ROLE_CERTIFIED']);
                $this->setRoles($roles);
            }
        }
        
        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setSeller($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getSeller() === $this) {
                $article->setSeller(null);
            }
        }

        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): static
    {
        // set the owning side of the relation if necessary
        if ($cart->getUser() !== $this) {
            $cart->setUser($this);
        }

        $this->cart = $cart;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Masterclass>
     */
    public function getMasterclasses(): Collection
    {
        return $this->masterclasses;
    }

    public function addMasterclass(Masterclass $masterclass): static
    {
        if (!$this->masterclasses->contains($masterclass)) {
            $this->masterclasses->add($masterclass);
            $masterclass->setAuthor($this);
        }

        return $this;
    }

    public function removeMasterclass(Masterclass $masterclass): static
    {
        if ($this->masterclasses->removeElement($masterclass)) {
            // set the owning side to null (unless already changed)
            if ($masterclass->getAuthor() === $this) {
                $masterclass->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Masterclass>
     */
    public function getPurchasedMasterclasses(): Collection
    {
        return $this->purchasedMasterclasses;
    }

    public function addPurchasedMasterclass(Masterclass $masterclass): static
    {
        if (!$this->purchasedMasterclasses->contains($masterclass)) {
            $this->purchasedMasterclasses->add($masterclass);
            $masterclass->addStudent($this);
        }

        return $this;
    }

    public function removePurchasedMasterclass(Masterclass $masterclass): static
    {
        if ($this->purchasedMasterclasses->removeElement($masterclass)) {
            $masterclass->removeStudent($this);
        }

        return $this;
    }

    public function hasPurchasedMasterclass(Masterclass $masterclass): bool
    {
        return $this->purchasedMasterclasses->contains($masterclass);
    }

    /**
     * @return Collection<int, MasterclassProgress>
     */
    public function getMasterclassProgress(): Collection
    {
        return $this->masterclassProgress;
    }

    public function addMasterclassProgress(MasterclassProgress $progress): static
    {
        if (!$this->masterclassProgress->contains($progress)) {
            $this->masterclassProgress->add($progress);
            $progress->setUser($this);
        }

        return $this;
    }

    public function removeMasterclassProgress(MasterclassProgress $progress): static
    {
        if ($this->masterclassProgress->removeElement($progress)) {
            // set the owning side to null (unless already changed)
            if ($progress->getUser() === $this) {
                $progress->setUser(null);
            }
        }

        return $this;
    }

    public function getProgressForMasterclass(Masterclass $masterclass): ?MasterclassProgress
    {
        foreach ($this->masterclassProgress as $progress) {
            if ($progress->getMasterclass() === $masterclass) {
                return $progress;
            }
        }
        
        return null;
    }
}
