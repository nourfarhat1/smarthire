<?php

namespace App\Entity;

use App\Repository\ComplaintTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComplaintTypeRepository::class)]
#[ORM\Table(name: 'complaint_type')]
class ComplaintType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(name: 'urgency_level', type: 'string', length: 50)]
    private ?string $urgencyLevel = null;

    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Complaint::class)]
    private Collection $complaints;

    public function __construct()
    {
        $this->complaints = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUrgencyLevel(): ?string
    {
        return $this->urgencyLevel;
    }

    public function setUrgencyLevel(string $urgencyLevel): self
    {
        $this->urgencyLevel = $urgencyLevel;

        return $this;
    }

    /**
     * @return Collection<int, Complaint>
     */
    public function getComplaints(): Collection
    {
        return $this->complaints;
    }

    public function addComplaint(Complaint $complaint): self
    {
        if (!$this->complaints->contains($complaint)) {
            $this->complaints->add($complaint);
            $complaint->setType($this);
        }

        return $this;
    }

    public function removeComplaint(Complaint $complaint): self
    {
        if ($this->complaints->removeElement($complaint)) {
            // set the owning side to null (unless already changed)
            if ($complaint->getType() === $this) {
                $complaint->setType(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name . ' (' . $this->urgencyLevel . ')';
    }
}
