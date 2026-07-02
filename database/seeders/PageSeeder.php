<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $pages = [
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'body' => "<p>Timber Trace Crafts is a one-person workshop in Avon Park, Florida, run by Michael J. Miller — a disabled veteran of Desert Storm who traded a uniform for a laser cutter and a workbench.</p>\n\n<h2>From Service to the Workshop</h2>\n<p>After years in the military, I wanted to build something with my hands again — something precise, something that outlasts a single use. Laser-cut woodworking gave me that: the same discipline and attention to detail I learned in service, now channeled into earrings, keepsake boxes, and engraved gifts instead.</p>\n\n<h2>Handmade, Start to Finish</h2>\n<p>Every design begins as a digital file and ends as something you can hold. I choose the wood, cut and engrave each piece myself, sand and finish it by hand, and pack every order for shipping — no factory, no warehouse, no assembly line. Small imperfections are part of the process; they're what make a handmade piece different from a mass-produced one.</p>\n\n<h2>A Veteran-Owned Small Business</h2>\n<p>Timber Trace Crafts is a sole proprietorship — just me, working out of Avon Park. When you order from this shop, you're supporting one veteran directly, not a company. I read every order, answer every message, and stand behind everything I make.</p>\n\n<p>Have a question about a piece, or want something custom? <a href=\"/contact\">Reach out</a> — I'd love to hear from you.</p>",
                'meta_title' => 'About Us | Timber Trace Crafts',
                'meta_description' => 'Meet Michael J. Miller, the disabled veteran and one-person maker behind Timber Trace Crafts, hand-crafting laser-cut wooden jewelry and gifts in Avon Park, FL.',
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact',
                'body' => '<h1>Contact Us</h1><p>Have a question or a custom order request? We\'d love to hear from you. Fill out the form below and we\'ll get back to you as soon as possible.</p>',
                'meta_title' => 'Contact | Timber Trace Crafts',
                'meta_description' => 'Get in touch with Timber Trace Crafts for questions, custom orders, and more.',
            ],
            [
                'slug' => 'faq',
                'title' => 'Frequently Asked Questions',
                'body' => '<h1>Frequently Asked Questions</h1><div class="faq-accordion"><div class="faq-item"><h2>What materials do you use?</h2><p>We use a variety of sustainably sourced hardwoods and plywood, including walnut, maple, cherry, and birch. Each product listing specifies the available wood species.</p></div><div class="faq-item"><h2>How long does production take?</h2><p>Most orders ship within 3–5 business days. Personalized items may require an additional 1–2 business days.</p></div><div class="faq-item"><h2>Do you accept custom orders?</h2><p>Yes! Please use our Contact page to describe your project and we\'ll get back to you with a quote.</p></div><div class="faq-item"><h2>What is your return policy?</h2><p>Please see our Return Policy page for full details. Personalized items are generally non-returnable unless there is a defect.</p></div></div>',
                'meta_title' => 'FAQ | Timber Trace Crafts',
                'meta_description' => 'Answers to common questions about materials, production times, shipping, and custom orders.',
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'body' => '<h1>Privacy Policy</h1><p><em>Last updated: '.$now->format('F j, Y').'</em></p><p>Timber Trace Crafts ("we", "us", or "our") is committed to protecting your personal information. This Privacy Policy explains how we collect, use, and share information about you when you use our website and purchase our products.</p><h2>Information We Collect</h2><p>We collect information you provide directly to us, such as your name, email address, shipping address, and payment information when you place an order.</p><h2>How We Use Your Information</h2><p>We use your information to process orders, send order confirmations and shipping updates, respond to your inquiries, and improve our services.</p><h2>Contact Us</h2><p>If you have questions about this Privacy Policy, please contact us at hello@timbertracecrafts.com.</p>',
                'meta_title' => 'Privacy Policy | Timber Trace Crafts',
                'meta_description' => 'Privacy Policy for Timber Trace Crafts — how we collect, use, and protect your information.',
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'body' => '<h1>Terms &amp; Conditions</h1><p><em>Last updated: '.$now->format('F j, Y').'</em></p><p>By placing an order with Timber Trace Crafts, you agree to the following terms and conditions.</p><h2>Orders</h2><p>All orders are subject to acceptance and availability. We reserve the right to refuse or cancel any order at our discretion.</p><h2>Personalized Items</h2><p>Personalized items are made to order and are generally non-refundable unless there is a manufacturing defect. Please double-check all personalization text before submitting your order.</p><h2>Intellectual Property</h2><p>All designs, images, and content on this website are the property of Timber Trace Crafts and may not be reproduced without written permission.</p><h2>Contact Us</h2><p>If you have questions about these Terms &amp; Conditions, please contact us at hello@timbertracecrafts.com.</p>',
                'meta_title' => 'Terms & Conditions | Timber Trace Crafts',
                'meta_description' => 'Terms and Conditions for purchasing from Timber Trace Crafts.',
            ],
            [
                'slug' => 'return-policy',
                'title' => 'Return Policy',
                'body' => '<h1>Return Policy</h1><p><em>Last updated: '.$now->format('F j, Y').'</em></p><h2>Returns</h2><p>We accept returns on non-personalized items within 14 days of delivery. Items must be unused and in original condition. To initiate a return, please contact us at hello@timbertracecrafts.com.</p><h2>Personalized Items</h2><p>Because personalized items are made specifically for you, we cannot accept returns on them unless there is a defect in materials or craftsmanship. If your personalized item arrives damaged or incorrect, please contact us within 7 days of delivery and we will make it right.</p><h2>Damaged or Defective Items</h2><p>If your order arrives damaged, please photograph the damage and contact us within 7 days of delivery. We will send a replacement at no charge.</p><h2>Refunds</h2><p>Once a return is received and inspected, we will process your refund to the original payment method within 5–10 business days.</p>',
                'meta_title' => 'Return Policy | Timber Trace Crafts',
                'meta_description' => 'Return and refund policy for Timber Trace Crafts orders.',
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                array_merge($page, ['updated_at' => $now])
            );
        }
    }
}
