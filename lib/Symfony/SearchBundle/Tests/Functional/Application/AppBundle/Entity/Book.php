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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A book.
 *
 * @see http://schema.org/Book Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(
 *     iri="http://schema.org/Book",
 *     attributes={
 *         "rollerworks_search" = {
 *             "contexts" = {
 *                 "_defaults" = {
 *                     "fieldset" = "Rollerworks\Bundle\SearchBundle\Tests\Functional\Application\AppBundle\Search\FieldSet\BookFieldSet"
 *                 },
 *                 "_any" = {
 *                     "doctrine_orm" = {
 *                         "mappings" = {
 *                             "id" = "id",
 *                             "title" = "title"
 *                         }
 *                     }
 *                 }
 *             }
 *         }
 *     }
 * )
 */
class Book
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The ISBN of the book
     *
     * @Assert\Isbn
     * @Assert\Type(type="string")
     * @ORM\Column(nullable=true)
     * @ApiProperty(iri="http://schema.org/isbn")
     */
    private $isbn;

    /**
     * @var string The title of the book
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @ORM\Column
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $title;

    /**
     * @var string A description of the item
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @ORM\Column(type="text")
     * @ApiProperty(iri="http://schema.org/description")
     */
    private $description;

    /**
     * @var string The author of this content or rating. Please note that author is special in that HTML 5 provides a special mechanism for indicating authorship via the rel tag. That is equivalent to this and may be used interchangeably
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @ORM\Column
     * @ApiProperty(iri="http://schema.org/author")
     */
    private $author;

    /**
     * @var \DateTimeImmutable The date on which the CreativeWork was created or the item was added to a DataFeed
     *
     * @Assert\Date
     * @Assert\NotNull
     * @ORM\Column(type="date")
     * @ApiProperty(iri="http://schema.org/dateCreated")
     */
    private $publicationDate;

    /**
     * Sets id.
     *
     * @param int $id
     *
     * @return static
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets isbn.
     *
     * @param string $isbn
     *
     * @return static
     */
    public function setIsbn($isbn)
    {
        $this->isbn = $isbn;

        return $this;
    }

    /**
     * Gets isbn.
     *
     * @return string
     */
    public function getIsbn()
    {
        return $this->isbn;
    }

    /**
     * Sets description.
     *
     * @param string $description
     *
     * @return static
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets author.
     *
     * @param string $author
     *
     * @return static
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Gets author.
     *
     * @return string
     */
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
