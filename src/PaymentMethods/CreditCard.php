<?php namespace TourChannel\Payments\PaymentMethods;

use Illuminate\Http\Request;
use TourChannel\Payments\Enum\StatusTransactionEnum;
use TourChannel\Payments\Service\RequestConnect;
use TourChannel\Payments\Traits\Customer;
use TourChannel\Payments\Traits\ShopCart;

/**
 * Class CreditCard
 * @package TourChannel\Payments\PaymentMethods
 */
class CreditCard
{
    /** Dados do cliente e do carrinho */
    use ShopCart, Customer;

    /** PATH da URl na API */
    const _PATH = '/pay/card';

    /**
     * Formatado do array que ira para API
     * @var array
     */
    protected $payload = [
        'order' => '',
        'amount' => 0,
        'cards' => [[
            'installments' => 0,
            'amount' => 0,
            'card' => [
                'number' => '',
                'holderName' => '',
                'expirationMonth' => 0,
                'expirationYear' => 0,
                'cvv' => ''
            ]
        ]]
    ];

    /**
     * Numero do pedido
     * @param $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->payload['order'] = $order;

        return $this;
    }

    /**
     * Valor da compra em centavos
     * @param $valor
     * @return $this
     */
    public function setAmount($valor)
    {
        // Valor da compra
        $this->payload['amount'] = $valor;
        // Valor do cartão = valor da compra
        $this->payload['cards'][0]['amount'] = $valor;

        return $this;
    }

    /**
     * Numero de parcelas
     * @param $count
     * @return $this
     */
    public function setInstallmentsCount($count)
    {
        $this->payload['cards'][0]['installments'] = $count;

        return $this;
    }

    /**
     * Número do cartão de crédito
     * @param $number_card
     * @return $this
     */
    public function setCreditCardNumber($number_card)
    {
        $this->payload['cards'][0]['card']['number'] = str_replace(" ", "", $number_card);

        return $this;
    }

    /**
     * Nome impresso no cartão
     * @param $holder_name
     * @return $this
     */
    public function setHolderName($holder_name)
    {
        $this->payload['cards'][0]['card']['holderName'] = $holder_name;

        return $this;
    }

    /**
     * Mês que expira o cartão
     * @param $exp_month
     * @return $this
     */
    public function setExpMonth($exp_month)
    {
        $this->payload['cards'][0]['card']['expirationMonth'] = $exp_month;

        return $this;
    }

    /**
     * Ano que expira o cartão
     * @param $exp_year
     * @return $this
     */
    public function setExpYear($exp_year)
    {
        $this->payload['cards'][0]['card']['expirationYear'] = $exp_year;

        return $this;
    }

    /**
     * Código de segurança
     * @param $security_code
     * @return $this
     */
    public function setSecurityCode($security_code)
    {
        $this->payload['cards'][0]['card']['cvv'] = $security_code;

        return $this;
    }

    /**
     * Efetua cobrança no cartão de crédito
     * @return array
     */
    public function pay()
    {
        // Realiza a transação
        $response_api = $this->chargeOnCard();

        // Verifica se deu certo
        if($response_api->status == StatusTransactionEnum::PAGO) {
            return [
                'approved' => true,
                'transaction_id' => $response_api->transactionId,
                'response' => $response_api
            ];
        }

        // Caso falhe a transação
        return [
            'approved' => false,
            'erro' => $response_api->message ?? "Não foi possível efetuar o pagamento!"
        ];
    }

    /**
     * Realiza a comunicação com a API de pagamentos
     * @return mixed|string
     */
    private function chargeOnCard() {

        // Connect da API de pagamentos
        $request_connect = new RequestConnect();

        // Realiza a comunicação
        return $request_connect->connect_api(self::_PATH, Request::METHOD_POST, $this->payload);
    }
}