<?php
use SilverStripe\Omnipay\GatewayFieldsFactory;
use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Omnipay\Service\ServiceFactory;
use SilverStripe\Omnipay\Model\Payment;
use Guzzle\Http\Client;
use Guzzle\Http\RequestOptions;

class Page_Controller extends ContentController
{
    /**
     * An array of actions that can be accessed via a request. Each array element should be an action name, and the
     * permissions or conditions required to allow the user to access it.
     *
     * <code>
     * array (
     *     'action', // anyone can access this action
     *     'action' => true, // same as above
     *     'action' => 'ADMIN', // you must have ADMIN permissions to access this action
     *     'action' => '->checkAction' // you can only access this action if $this->checkAction() returns true
     * );
     * </code>
     *
     * @var array
     */
    private static $allowed_actions = array(
      'newForm', 'payment'
    );

    public function init()
    {
        parent::init();
        // You can include any CSS or JS required by your project here.
        // See: http://doc.silverstripe.org/framework/en/reference/requirements
    }

    public function newForm()
    {
      if (!isset($_GET['success']) && !isset($_GET['failed'])) {
        $factory = new GatewayFieldsFactory($gateway);
        $fields = new FieldList(
          TextField::create('description'),
          NumericField::create('value')
        );
        return Form::create(
          $this,
          "newForm",
          $fields,
          FieldList::create(FormAction::create("payment")->setTitle('Pay Now')
        )
      );
    }
  }

    public function payment($data, Form $form) {
      $gateway = \Omnipay\Omnipay::create('Mollie');
      $gateway->setApiKey('test_fwtHbaF8cjHnTeQJ4xVFqthbj8aVEP');
      try {
        $response = $gateway->purchase(
          [
        "amount" => floatval($data['description']),
        "currency" => "EUR",
        "description" => $data['value'],
        "returnUrl" => 'http://localhost/silverstripe/?payment_status',
          ]
        )->send();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      if ($response->isSuccessful()) {
    // Payment was successful

}

       elseif ($response->isRedirect()) {
         session_start();
         $_SESSION['pay_id'] = ($response->getData())['id'];
         $response->redirect();
       } else {
         echo $response->getMessage();
       }
     }
  public function checkPayment() {
    if  (isset($_GET['payment_status'])) {
      session_start();
      $id = $_SESSION['pay_id'];
      $token = 'test_fwtHbaF8cjHnTeQJ4xVFqthbj8aVEP';
      $ch = curl_init('https://api.mollie.com/v2/payments/'.$id);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
      ));
      $data = curl_exec($ch);
      $info = curl_getinfo($ch);
      curl_close($ch);

      if (json_decode($data)->status == 'paid') {
        header("Location: http://localhost/silverstripe/?success");
      } else {
        header("Location: http://localhost/silverstripe/?failed");
      }
    }
  }
   public function status() {
     if (isset($_GET['success'])) {
       echo 'You paid for it';
     } elseif (isset($_GET['failed'])) {
       echo "You didn't pay";
     }
   }
}
