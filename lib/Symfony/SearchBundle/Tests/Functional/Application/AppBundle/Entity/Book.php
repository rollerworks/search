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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Rollerworks\Bundle\SearchBundle\Tests\Functional\Application\AppBundle\Search\FieldSet\BookFieldSet;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see http://schema.org/Book Documentation on Schema.org
 */
#[ApiResource(operations: [
    new GetCollection(
        extraProperties: [
            'rollerworks_search' => [
                'contexts' => [
                    '_defaults' => [
                        'fieldset' => BookFieldSet::class,
                    ],
                    '_any' => [
                        'doctrine_orm' => [
                            'mappings' => [
                                'id' => 'id',
                                'title' => 'title',
                            ],
                        ],
                    ],
                ],
            ],
        ]),
])]
#[ORM\Entity] class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ApiProperty(identifier: true)]
    private int $id;

    /**
     * @var string The ISBN of the book
     */
    #[Assert\Type(type: 'string')]
    #[Assert\Isbn]
    #[ORM\Column(nullable: true)]
    private string $isbn;

    /**
     * @var string The title of the book
     */
    #[Assert\Type(type: 'string')]
    #[Assert\NotBlank]
    #[ORM\Column]
    private string $title;

    /**
     * @var string A description of the item
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    #[ORM\Column(type: 'text')]
    private string $description;

    /**
     * @var string The author of this content or rating. Please note that author is special in that HTML 5 provides a special mechanism
     *             for indicating authorship via the rel tag. That is equivalent to this and may be used interchangeably
     */
    #[Assert\Type(type: 'string')]
    #[Assert\NotBlank]
    #[ORM\Column]
    private string $author;

    /**
     * @var \DateTimeImmutable The date on which the CreativeWork was created or the item was added to a DataFeed
     */
    #[Assert\Date]
    #[Assert\NotNull]
    #[ORM\Column(type: 'date')]
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

    public function setIsbn($isbn)
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getIsbn()
    {
        return $this->isbn;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    public function setPublicationDate($publicationDate): void
    {
        $this->publicationDate = $publicationDate;
    }
}
