<?php /** @noinspection PhpParamsInspection */

namespace App\Controller;


use Omnipay\Common\GatewayInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\DisabledException;
use function array_key_exists;
use Omnipay\Omnipay;

class PaypalController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #


    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #



    # ------------------------------------------------- METHODS ------------------------------------------------------ #
    /**
     * Puerta de enlace para conectar con tu cuenta de Paypal
     * @return GatewayInterface
     */
    public function gateway(): GatewayInterface
    {
        $gateway = Omnipay::create('PayPal_Express');
        $gateway->setUsername("sb-8gkce7948006@business.example.com");
        $gateway->setPassword("Aeoednv3-zOUxSY7VH_q9Y3U-QYqRs9HkEUBTMn17CbIGJ3JZfO1qbkEeHhXcXWCBAHHHJqaLOmawJiE");
        $gateway->setSignature("EMBoZgWviGWhRqyK-t-h8d_Qt04niR-g-UETrCjzruovzMeM4KIF3UayOdMpXPxtwQDnF0n4Rg0Fn4DB");
        $gateway->setTestMode(true);
        return $gateway;
    }

    /**
     * @param array $parameters
     * @return mixed
     */
    public function purchase(array $parameters): bool
    {
        return $this->gateway()
            ->purchase($parameters)
            ->send();
    }

    /**
     * @param int $amount
     * @return string
     */
    public function formatAmount(int $amount):string
    {
        return number_format($amount, 2, '.', '');
    }

    public function getCancelUrl($order = "")
    {
        return $this->route('http://phpstack-275615-1077014.cloudwaysapps.com/cancel.php', $order);
    }

    public function getReturnUrl($order = "")
    {
        return $this->route('http://phpstack-275615-1077014.cloudwaysapps.com/return.php', $order);
    }
    public function route($name, $params)
    {
        return $name; // ya change hua hai
    }


    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
