<?php

namespace App\Entity;

use App\Repository\ProductOrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProductOrderRepository::class)]
class ProductOrder
{
    #[ORM\Id]
    #[ORM\ManyToOne(cascade: ["persist"], inversedBy: 'productOrders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['show_order'])]
    private ?Product $product = null;

    #[ORM\Id]
    #[ORM\ManyToOne(cascade: ["persist"], inversedBy: 'productOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $orderr = null;

    #[ORM\Column]
    #[Groups(['show_order'])]
    private ?int $amount = null;

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getOrderr(): ?Order
    {
        return $this->orderr;
    }

    public function setOrderr(?Order $orderr): self
    {
        $this->orderr = $orderr;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }
}
