<?php

declare(strict_types=1);

namespace App\Services;

use Nette;

/**
 * Odoslanie e-mailov
 * Last change 01.09.2021
 * 
 * @github     Forked from petrbrouzda/RatatoskrIoT
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2021 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
 */
class MailService {
  use Nette\SmartObject;
  
  public $mailFrom;
  public $mailAdmin;

  /** @var Nette\Application\LinkGenerator */
	private $linkGenerator;

	/** @var Nette\Bridges\ApplicationLatte\TemplateFactory */
	private $templateFactory;

	public function __construct($mailFrom,
                              $mailAdmin,
                              Nette\Application\LinkGenerator $linkGenerator,
                              Nette\Bridges\ApplicationLatte\TemplateFactory $templateFactory
                              ) {
    $this->mailFrom = $mailFrom;
    $this->mailAdmin = $mailAdmin;

    $this->linkGenerator = $linkGenerator;
		$this->templateFactory = $templateFactory;
  }

  /** 
   * Umožňuje využívať v html tele e-mailu v šablonách odkazy pomocou atribútu n:href nebo značky {link} */
  private function createTemplate(): Nette\Application\UI\Template {
		$template = $this->templateFactory->createTemplate();
		$template->getLatte()->addProvider('uiControl', $this->linkGenerator);
		return $template;
	}

	public function createEmail($to, $template_file_road, $params): Nette\Mail\Message {
		$template = $this->createTemplate();
		$html = $template->renderToString( $template_file_road, $params);

		$mail = new Nette\Mail\Message;
		$mail->setHtmlBody($html)
          ->addTo($to)
          ->setFrom( $this->mailFrom );
		return $mail;
	}

  public function sendMailAdmin( $subject, $text ): void {
    $this->sendMail(  $this->mailAdmin,
                      $subject,
                      $text
    );
  }

  public function sendMail( $to, $subject, $text ) {
    $mail = new Nette\Mail\Message;
    $mail->setFrom( $this->mailFrom )
        ->addTo($to)
        ->setSubject( "IoT-server: {$subject}")
        ->setHtmlBody($text);
    try {
      $sendmail = new Nette\Mail\SendmailMailer;
      $sendmail->send($mail);
    } catch (Exception $e) {
      throw new SendException($e->getMessage());
    }
  }

  /**
   * Funkcia na odosielanie e-mailov využívajúca aj latte a šablóny */
  public function sendMail2( $to, $template_file_road, $params_to_template ) {
    //dumpe($to, $template_file_road, $params_to_template);
    try {
      $sendmail = new Nette\Mail\SendmailMailer;
      $sendmail->send($this->createEmail($to, $template_file_road, $params_to_template));
    } catch (Nette\Mail\SendException $e) {
      throw new SendException($e->getMessage());
    }
  }
}

class SendException extends \Exception
{
}