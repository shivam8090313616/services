<?php

print_r($_GET); exit;


  ?>
  <!DOCTYPE html">
  <html>
    <head>
      <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
      <title>Payment Response Page</title>
      <style>
        .topbar {
          background-color: #d7f3d1;
          padding: 10px;
          text-align: center;
          color: #268825;
        }
        .failed .topbar {
          background-color: #f3d1d1;
          padding: 10px;
          text-align: center;
          color: #882525;
        }
        .checkmark {
          background: #6ad825;
          padding: 3px;
          border-radius: 50%;
          color: #ffffff;
        }
        .failed .checkmark {
          background: #d82525;
          padding: 3px;
          border-radius: 50%;
          color: #ffffff;
        }
        .successtext {
          color: #268825;
          font-weight: 600;
        }
        .failed .successtext{
          color: #882525;
          font-weight: 600;
        }
        .transbody{
          position: absolute;
          top: 50%;
          left: 50%;
          margin: 0;
          -ms-transform: translate(-50%, -50%);
          transform: translate(-50%,-50%);
        }
        .receipt {
          text-align: center;
          font-weight: 600;
          color: firebrick;
          text-decoration: underline;
        }
        h1.payment-status {
          font-weight: 500;
          color: #424242;
          text-align: center;
        }
        article.payment-status {
          color: #424242;
          text-align: center;
        }
        .pinfo{
          border: 1px solid #8c8c8c;		
          padding: 20px;
        }
        .pinfo td:nth-child(1){
          text-align: right;
          font-size: 13px;
          padding: 12px 20px;
          font-weight: 600;
        }
        .pinfo td:nth-child(2){
          text-align:left;
          font-size: 12px;
          padding: 5px 10px;
        }
        .btn-below {
          text-align: center;
          margin-top: 20px;
        }
        .back-btn {
          color: #fff;
          background-color: #337ab7;
          border-color: #2e6da4;
          display: inline-block;
          padding: 6px 12px;
          margin-bottom: 0;
          font-size: 14px;
          font-weight: 400;
          line-height: 1.42857143;
          text-align: center;
          white-space: nowrap;
          vertical-align: middle;
          -ms-touch-action: manipulation;
          touch-action: manipulation;
          cursor: pointer;
          -webkit-user-select: none;
          -moz-user-select: none;
          -ms-user-select: none;
          user-select: none;
          background-image: none;
          border: 1px solid transparent;
          border-radius: 4px;
          text-decoration: none;
        }
      </style>
    </head>
    <body>
      <div class="container failed">
        <div class="topbar">
          <span class="checkmark">&#10004;</span>
          <span class="successtext">&nbsp;Failed!</span> Your transaction details are below.
        </div>
        <div class="transbody">
          <article class="receipt">RECEIPT</article>
          <h1 class="payment-status">
            <span class="checkmark">&#10004;</span>
            Transaction Failed
          </h1>
          <article class="payment-status">
            This is your receipt for the current transaction: $<?php // echo($usd); ?>
          </article>
          <h4>Receipt from Payoneer</h4>
          <table class="pinfo">
            <tr>
              <td>Description</td>
              <td><?php // echo($error_Message); ?></td>
            </tr>
            <tr>
              <td>Amount (USD)</td>
              <td>$<?php // echo($usd); ?></td>
            </tr>
            <tr>
              <td>Transaction ID</td>
              <td><?php // echo($txnid); ?></td>
            </tr>
            <tr>
              <td>Created on</td>
              <td><?php // echo($datetime); ?> (GMT+5:30)</td>
            </tr>
          </table>
          <div class="btn-below">
            <a href="add-fund.php" class="back-btn">Back to Wallet</a>
          </div>
        </div>
      </div>
    </body>
  </html>
