<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $orderRef = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Article $article = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $articleName = null;
    
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Masterclass $masterclass = null;
    
    #[ORM\Column(length: 20)]
    private string $itemType = 'article';

    public function __construct()
    {
        $this->quantity = 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(?Order $orderRef): static
    {
        $this->orderRef = $orderRef;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;
        
        // Stocker le nom et le prix de l'article au moment de la commande
        if ($article) {
            $this->setArticleName($article->getName());
            $this->setPrice($article->getPrice());
            $this->setItemType('article');
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
        
        // Stocker le nom et le prix de la masterclass au moment de la commande
        if ($masterclass) {
            $this->setArticleName('Masterclass: ' . $masterclass->getTitle());
            $this->setPrice($masterclass->getPrice());
            $this->setItemType('masterclass');
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getArticleName(): ?string
    {
        return $this->articleName;
    }

    public function setArticleName(string $articleName): static
    {
        $this->articleName = $articleName;

        return $this;
    }
    
    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): static
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * Calcule le total de cet élément de commande (prix * quantité)
     */
    public function getTotal(): float
    {
        return $this->getPrice() * $this->getQuantity();
    }
}
