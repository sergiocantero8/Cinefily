<?php

namespace App\Entity;

use App\Repository\LogInfoRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogInfoRepository::class)
 */
class LogInfo
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const TYPE_ERROR = 0;
    public const TYPE_WARNING = 1;
    public const TYPE_SUCCESS = 2;
    public const TYPE_INFO = 3;


    # -------------------------------------------------- CONST ------------------------------------------------------- #

    /**
     * LogInfo constructor.
     * @param int $type
     * @param string $info
     */
    public function __construct(int $type, string $info)
    {
        $this->setDate(new DateTime());
        $this->setType($type);
        $this->setInfo($info);
    }
    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="text")
     */
    private $info;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;


    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(string $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    # ------------------------------------------------ LIFECYCLE ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


    /**
     * @param int $type
     * @return string
     */
    public static function convertTypeLogInfo(int $type): string
    {
        switch ($type):
            case static::TYPE_ERROR:
                $value = "ERROR";
                break;

            case static::TYPE_WARNING:
                $value = "WARNING";
                break;

            case static::TYPE_SUCCESS:
                $value = "EXITO";
                break;

            case static::TYPE_INFO:
                $value = "INFO";
                break;

            default:
                $value = "DESCONOCIDO";
                break;
        endswitch;

        return $value;
    }
}
