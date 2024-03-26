<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Functional\Application\AppBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A review of an item - for example, of a restaurant, movie, or store.
 *
 * @see http://schema.org/Review Documentation on Schema.org
 */
#[ORM\Entity]
#[ApiResource]
class Review
{
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Id]
    private int $id;

    /**
     * @var string The actual body of the review
     */
    #[ORM\Column(nullable: true, type: 'text')]
    #[Assert\Type(type: 'string')]
    private $body;

    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 0, max: 5)]
    #[Assert\Type(type: 'integer')]
    private int $rating;

    /**
     * @var Book The item that is being reviewed/rated
     */
    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private Book $book;

    /**
     * @var string Author the author of the review
     */
    #[ORM\Column(nullable: true, type: 'text')]
    private string $author;

    /**
     * @var \DateTimeImmutable Author the author of the review
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private \DateTimeImmutable $publicationDate;

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    public function getRating()
    {
        return $this->rating;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getBook()
    {
        return $this->book;
    }

    public function setBook(Book $book): void
    {
        $this->book = $book;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(\DateTimeImmutable $publicationDate): void
    {
        $this->publicationDate = $publicationDate;
    }
}
