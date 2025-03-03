<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Article $article = null;
    
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Masterclass $masterclass = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $addedAt = null;
    
    #[ORM\Column(length: 20)]
    private ?string $itemType = null;

    public function __construct()
    {
        $this->quantity = 1;
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;
        if ($article) {
            $this->setItemType('article');
            $this->masterclass = null;
        }

        return $this;
    }
    
    public function getMasterclass(): ?Masterclass
    {
        return $this->masterclass;
    }

    public function setMasterclass(?Masterclass $masterclass): static
    {
        $this->masterclass = $masterclass;
        if ($masterclass) {
            $this->setItemType('masterclass');
            $this->article = null;
        }

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getAddedAt(): ?\DateTimeImmutable
    {
        return $this->addedAt;
    }
    
    public function getItemType(): ?string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): static
    {
        $this->itemType = $itemType;

        return $this;
    }
    
    /**
     * Récupère le nom de l'élément (article ou masterclass)
     */
    public function getName(): string
    {
        if ($this->itemType === 'article' && $this->article) {
            return $this->article->getName();
        } elseif ($this->itemType === 'masterclass' && $this->masterclass) {
            return $this->masterclass->getTitle();
        }
        
        return 'Élément inconnu';
    }

    /**
     * Calcule le total de cet élément du panier (prix * quantité)
     */
    public function getTotal(): float
    {
        if ($this->itemType === 'article' && $this->article) {
            return $this->article->getPrice() * $this->quantity;
        } elseif ($this->itemType === 'masterclass' && $this->masterclass) {
            return $this->masterclass->getPrice(); // Les masterclasses sont toujours achetées en quantité 1
        }
        
        return 0;
    }
    
    /**
     * Récupère le prix unitaire de l'élément
     */
    public function getUnitPrice(): float
    {
        if ($this->itemType === 'article' && $this->article) {
            return $this->article->getPrice();
        } elseif ($this->itemType === 'masterclass' && $this->masterclass) {
            return $this->masterclass->getPrice();
        }
        
        return 0;
    }

    /**
     * Vérifie si cet élément est identique à un autre (même article ou même masterclass)
     */
    public function equals(CartItem $item): bool
    {
        if ($this->itemType === 'article' && $item->getItemType() === 'article') {
            return $this->article && $item->getArticle() && $this->article->getId() === $item->getArticle()->getId();
        } elseif ($this->itemType === 'masterclass' && $item->getItemType() === 'masterclass') {
            return $this->masterclass && $item->getMasterclass() && $this->masterclass->getId() === $item->getMasterclass()->getId();
        }
        
        return false;
    }
}
