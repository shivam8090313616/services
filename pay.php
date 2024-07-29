<?php
    print_r($_GET);
?>

<div class="col-lg-12">
                        <div class="text-right">
                          <form action="https://www.coinpayments.net/index.php" method="post">
                            <input type="hidden" name="cmd" value="_pay">
                            <input type="hidden" name="reset" value="1">
                            <input type="hidden" name="merchant" value="0a70094b961261b1bdf613b40a00f6dd">
                            <input type="hidden" name="item_name" value="Add to Wallet">
                            <input type="hidden" name="currency"  value="USD">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="amountf" value="50">
                            <input type="hidden" name="allow_quantity" value="0">
                            <input type="hidden" name="want_shipping" value="0">
                            <input type="hidden" name="allow_extra"  value="1">
                            <input type="hidden" name="success_url" value="https://services.7searchppc.in/pay.php">
                            <input type="hidden" name="cancel_url" value="https://services.7searchppc.in/pay.php">
                            <input type="hidden" name="ipn_url" value="https://services.7searchppc.in/pay.php">
                            <input type="image" src="https://www.coinpayments.net/images/pub/buynow-wide-blue.png" alt="Buy Now with CoinPayments.net">
                          </form>
                        </div>
                      </div>