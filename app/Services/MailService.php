<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Mail;

/**
 * Odoslanie e-mailov
 * Last change 29.04.2026
 * 
 * @github     Forked from petrbrouzda/RatatoskrIoT
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.3
 */
class MailService {
	use Nette\SmartObject;
	
	public $mailFrom;
	public $mailAdmin;

	/** @var Nette\Application\LinkGenerator */
	private $linkGenerator;

	/** @var Nette\Bridges\ApplicationLatte\TemplateFactory */
	private $templateFactory;

	public function __construct(
															Nette\Application\LinkGenerator $linkGenerator,
															LatteFactory $latteFactory,
															string $mailFrom = "",
															string $mailAdmin = "",
															) {
		$this->mailFrom = $mailFrom;
		$this->mailAdmin = $mailAdmin;

		$this->linkGenerator = $linkGenerator;
		$this->templateFactory = $latteFactory;
	}

	/** 
	 * Umožňuje využívať v html tele e-mailu v šablonách odkazy pomocou atribútu n:href nebo značky {link} */
	private function createTemplate(): Nette\Application\UI\Template {
		$template = $this->templateFactory->createTemplate();
		$template->getLatte()->addProvider('uiControl', $this->linkGenerator);
		return $template;
	}

	public function createEmail(string $to, string $template_file_road, array $params): Mail\Message {
		$latte = $this->latteFactory->create();
		$html = $latte->renderToString($template_file_road, $params);

		$mail = new Mail\Message;
		$mail->setHtmlBody($html)
					->addTo($to)
					->setFrom( $this->mailFrom );
		return $mail;
	}

	public function sendMailAdmin( string $subject, string $text ): void {
		$this->sendMail(  $this->mailAdmin,
											$subject,
											$text
		);
	}

	public function sendMail( string $to, string $subject, string $text ) {
		$mail = new Mail\Message;
		$mail->setFrom( $this->mailFrom )
				->addTo($to)
				->setSubject( "IoT-server: {$subject}")
				->setHtmlBody($text);
		try {
			$sendmail = new Mail\SendmailMailer;
			$sendmail->send($mail);
		} catch (\Exception $e) {
			throw new SendException($e->getMessage());
		}
	}

	/**
	 * Funkcia na odosielanie e-mailov využívajúca aj latte a šablóny */
	public function sendMail2( string $to, string $template_file_road, array $params_to_template ) {
		//dumpe($to, $template_file_road, $params_to_template);
		try {
			$sendmail = new Mail\SendmailMailer;
			$sendmail->send($this->createEmail($to, $template_file_road, $params_to_template));
		} catch (Mail\SendException $e) {
			throw new SendException($e->getMessage());
		}
	}
}

class SendException extends \Exception
{
}