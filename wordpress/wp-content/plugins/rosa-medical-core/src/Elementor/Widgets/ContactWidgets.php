<?php

declare(strict_types=1);

namespace RosaMedical\Core\Elementor\Widgets;

final class ContactHeroWidget extends AbstractRosaSectionWidget
{
    public function get_name(): string { return 'rosa-page-hero-contact'; }
    public function get_title(): string { return 'Rosa Contact — Page Hero'; }
    protected function register_controls(): void
    {
        $this->beginContentSection('Page Hero');
        $this->addText('page_eyebrow', 'Eyebrow', 'ROSA');
        $this->addText('page_title', 'Heading', 'Contact us');
        $this->addTextarea('page_body', 'Body', 'Get in touch and let us know how we can help.');
        $this->end_controls_section();
    }
    protected function render(): void { $this->renderSection('page-hero', [], ['section' => 'contact']); }
}

final class ContactLayoutWidget extends AbstractRosaSectionWidget
{
    public function get_name(): string { return 'rosa-contact-layout'; }
    public function get_title(): string { return 'Rosa Contact — Conversation & Message'; }
    protected function register_controls(): void
    {
        $this->beginContentSection('Conversation & Message');
        $this->addText('conversation_eyebrow', 'Conversation eyebrow', 'Contact Rosa');
        $this->addText('conversation_title', 'Conversation heading', 'Let’s start talking about your requirements.');
        $this->addTextarea('conversation_body', 'Conversation body', 'Share the instrument family, catalogue reference or configuration you need and our team will help you prepare the next step.');
        $this->addText('location_label', 'Location label', 'Location');
        $this->addText('phone_label', 'Phone label', 'Call us');
        $this->addText('email_label', 'Email label', 'Email us');
        $this->addText('message_title', 'Message card heading', 'Send us a message');
        $this->addText('field_name', 'Name field label', 'Name');
        $this->addText('field_phone', 'Phone field label', 'Phone');
        $this->addText('field_subject', 'Subject field label', 'Subject');
        $this->addText('field_message', 'Message field label', 'Message');
        $this->addText('send_email', 'Email action label', 'Send by email');
        $this->end_controls_section();
    }
    protected function render(): void { $this->renderSection('contact-layout'); }
}

/**
 * Legacy widget retained so existing Elementor documents remain loadable.
 * The current live Contact composition integrates location into the main card,
 * therefore this historical widget intentionally has no public output.
 */
final class ContactMapWidget extends AbstractRosaSectionWidget
{
    public function get_name(): string { return 'rosa-contact-map'; }
    public function get_title(): string { return 'Rosa Contact — Legacy Location'; }
    protected function register_controls(): void
    {
        $this->beginContentSection('Legacy Location');
        $this->addText('map_eyebrow', 'Eyebrow', 'Location');
        $this->addText('map_button', 'Map button label', 'Search on maps');
        $this->end_controls_section();
    }
    protected function render(): void {}
}
