<?php
/*
NOTE: You must create a transactional email template via Magento admin panel first. Then change the variable $templateId on this page to the new template ID.
*/
class Xanix_Vorkassemail_Model_Observer extends Mage_Core_Model_Abstract 
{
    public function sendVorkassemail($observer)
    {
		$event = $observer->getEvent();
		if(!$order = $event->getOrder())
		{
			$orderId = Mage::getSingleton('checkout/type_onepage')->getCheckout()->getLastOrderId();
			$order = Mage::getModel('sales/order')->load($orderId);
		}
		$payment = $order->getPayment()->getMethod();
		
		if($payment == 'payone_vor') //if payment_method is Payone Vorkasse then send the transactional email
		{
			$isAddHistoryActive = true; //history and statuschange is saved in orders
							
			// Transactional Email Template's ID
			$templateId  = 1;	//template used for Vorkasse - ****** CHANGE HERE ******
				
			// Set sender information          
			$senderName = Mage::getStoreConfig('trans_email/ident_support/name');
			$senderEmail = Mage::getStoreConfig('trans_email/ident_support/email');    
			$sender = array('name' => $senderName,'email' => $senderEmail);
			
			// Get Store ID    
			$storeid = Mage::app()->getStore()->getId();
		
			$billingDetails = $order->getBillingAddress();
			$paymentMethod = $order->getData();
			
			if($isAddHistoryActive)
			{
				//add history comment
				$comment = 'Eine automatische Mail "Warten auf Bezahlung" wurde aufgrund der Zahlart "Vorkasse" gesendet';
				$order->addStatusHistoryComment($comment);
				$order->save();
			}
			   
			$recepientEmail = $billingDetails->getEmail();
			$recepientName = $billingDetails->getFirstname()." ".$billingDetails->getLastname();
				
			$payment = $order->getPayment();
			$verwendungszweck = $order->getIncrementId();
			$grandtotal = $order->getGrandTotal();
			
			$paymentBlock = Mage::helper('payment')->getInfoBlock($order->getPayment())->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
			$rechnungsinformation = $order->getBillingAddress()->format('html');

			 // Set variables that can be used in email template
			$vars = array(
					'customerEmail' => $recepientEmail,
					'zahlart' => $paymentBlockHtml,
					'rechnungsinformation' => $rechnungsinformation,
					'verwendungszweck' => $verwendungszweck,
					'order' => $order,
					);
		
			// Send Transactional Email
			$mailTemplate = Mage::getModel('core/email_template');
			$mailTemplate->setTemplateSubject('subject');
			$mailTemplate->setDesignConfig(array('area' => 'frontend'));
			$mailTemplate->sendTransactional($templateId, $sender, $recepientEmail, $recepientName, $vars, $storeId);
		}
		
        return true;
    }
}
