<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PolicyPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'return-policy',
                'title' => 'Return Policy',
                'meta_title' => 'Return & Refund Policy | Timber Trace Crafts',
                'meta_description' => 'Our return, refund, and dispute policy for Timber Trace Crafts orders.',
                'body' => <<<'HTML'
<p><em>Last updated: June 22, 2026</em></p>

<h2>Returns</h2>
<p>We accept returns on non-personalized items within <strong>14 days of delivery</strong>. Items must be unused and in their original condition. To initiate a return, please contact us at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a> with your order number and reason for return. We will provide a return shipping address within 1–2 business days.</p>

<h2>Personalized Items</h2>
<p>Because personalized items are made specifically for you, we cannot accept returns on them unless there is a defect in materials or craftsmanship. If your personalized item arrives damaged or incorrect, please contact us within <strong>7 days of delivery</strong> with photos and we will remake or refund the item at no cost to you.</p>

<h2>Damaged or Defective Items</h2>
<p>If your order arrives damaged, please photograph the damage and contact us within 7 days of delivery at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a>. We will send a replacement or issue a full refund at your preference.</p>

<h2>Refunds</h2>
<p>Once a return is received and inspected, we will process your refund to the original payment method within <strong>5–10 business days</strong>. You will receive an email confirmation when your refund has been issued. Please allow additional time for your bank or card issuer to post the credit.</p>

<h2>Exchanges</h2>
<p>We do not offer direct exchanges at this time. If you would like a different item, please return your original purchase (if eligible) and place a new order.</p>

<h2>Disputes & Chargebacks</h2>
<p>We are committed to resolving any issue with your order quickly and fairly. <strong>Before filing a dispute or chargeback with your bank or credit card company, please contact us directly</strong> at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a>. We respond to all inquiries within 1–2 business days and will work with you to find a resolution. Unauthorized chargebacks may result in your account being flagged and orders being cancelled.</p>

<h2>Contact Us</h2>
<p>Questions about a return or refund? Reach us at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a> or use our <a href="/contact">contact form</a>. We're happy to help.</p>
HTML,
            ],

            [
                'slug' => 'shipping-policy',
                'title' => 'Shipping Policy',
                'meta_title' => 'Shipping Policy | Timber Trace Crafts',
                'meta_description' => 'Shipping times, carriers, and delivery information for Timber Trace Crafts orders.',
                'body' => <<<'HTML'
<p><em>Last updated: June 22, 2026</em></p>

<h2>Processing Time</h2>
<p>All orders are handmade to order in our studio. Please allow the following processing times before your order ships:</p>
<ul>
    <li><strong>Non-personalized items:</strong> 3–5 business days</li>
    <li><strong>Personalized items:</strong> 5–10 business days</li>
</ul>
<p>During peak seasons (holidays, special promotions) processing times may be extended. We will notify you if your order will be delayed beyond our standard window.</p>

<h2>Shipping Methods & Costs</h2>
<p>We ship all orders via USPS. The following options are available at checkout:</p>
<ul>
    <li><strong>USPS Ground Advantage — Free</strong> on every order. Estimated delivery 2–5 business days after shipment.</li>
    <li><strong>USPS Priority Mail — $7.00.</strong> Estimated delivery 1–3 business days after shipment.</li>
    <li><strong>USPS Priority Mail Express — $35.00.</strong> Estimated delivery 1–2 business days after shipment, including weekends.</li>
</ul>
<p>All shipping rates are flat-rate and apply to orders shipped within the contiguous United States.</p>

<h2>Order Tracking</h2>
<p>Once your order ships, you will receive a shipping confirmation email with a tracking number. You can track your package directly on the carrier's website or through your account order history.</p>

<h2>Domestic Shipping</h2>
<p>We currently ship to all 50 U.S. states. Orders to Hawaii, Alaska, and U.S. territories may require additional transit time and shipping costs.</p>

<h2>International Shipping</h2>
<p>We do not currently offer international shipping. We hope to expand in the future — sign up for our newsletter to be notified.</p>

<h2>Lost or Undelivered Packages</h2>
<p>If your tracking shows your package as delivered but you have not received it, please:</p>
<ol>
    <li>Check around your property, with neighbors, and at any alternate delivery locations.</li>
    <li>Wait 24–48 hours, as carriers occasionally mark packages as delivered early.</li>
    <li>Contact us at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a> if the package still cannot be located.</li>
</ol>
<p>We will work with the carrier to investigate. Packages confirmed lost by the carrier will be replaced or refunded.</p>

<h2>Address Accuracy</h2>
<p>Please double-check your shipping address at checkout. We are not responsible for packages delivered to an incorrect address provided at the time of order. If you notice an error after placing your order, contact us immediately at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a> — we will do our best to correct it before the order ships.</p>

<h2>Contact Us</h2>
<p>For shipping questions, email us at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a> or use our <a href="/contact">contact form</a>.</p>
HTML,
            ],

            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'meta_title' => 'Privacy Policy | Timber Trace Crafts',
                'meta_description' => 'How Timber Trace Crafts collects, uses, and protects your personal information.',
                'body' => <<<'HTML'
<p><em>Last updated: June 22, 2026</em></p>

<p>Timber Trace Crafts ("we," "us," or "our") is committed to protecting your personal information. This Privacy Policy describes how we collect, use, share, and protect information about you when you visit our website or make a purchase.</p>

<h2>Information We Collect</h2>
<p>We collect information you provide directly to us, including:</p>
<ul>
    <li><strong>Account information:</strong> name, email address, and password if you create an account.</li>
    <li><strong>Order information:</strong> shipping address, billing address, and order history.</li>
    <li><strong>Payment information:</strong> credit or debit card details. Payment information is processed and stored by Stripe, Inc. — we never store your full card number on our servers.</li>
    <li><strong>Communications:</strong> messages you send us through our contact form or email.</li>
    <li><strong>Newsletter:</strong> email address if you subscribe to our mailing list.</li>
</ul>

<p>We also collect certain information automatically when you visit our website:</p>
<ul>
    <li><strong>Log data:</strong> IP address, browser type, pages visited, and referring URLs.</li>
    <li><strong>Cookies:</strong> small data files stored on your device to maintain your session, remember your cart, and analyze site usage. See the Cookies section below.</li>
</ul>

<h2>How We Use Your Information</h2>
<p>We use the information we collect to:</p>
<ul>
    <li>Process and fulfill your orders, including sending order confirmations and shipping notifications.</li>
    <li>Communicate with you about your account or orders.</li>
    <li>Send marketing emails and newsletters (only with your consent — you can unsubscribe at any time).</li>
    <li>Improve our website and product offerings.</li>
    <li>Detect and prevent fraud or unauthorized transactions.</li>
    <li>Comply with legal obligations.</li>
</ul>

<h2>Sharing Your Information</h2>
<p>We do not sell your personal information. We share your information only with the third-party service providers necessary to operate our business:</p>
<ul>
    <li><strong>Stripe, Inc.</strong> — payment processing. Stripe's privacy policy is available at <a href="https://stripe.com/privacy" target="_blank" rel="noopener noreferrer">stripe.com/privacy</a>.</li>
    <li><strong>Shipping carriers (USPS, UPS)</strong> — your name and shipping address are shared with the carrier to deliver your order.</li>
    <li><strong>Email service providers</strong> — used to send order confirmations, shipping updates, and newsletters.</li>
    <li><strong>Analytics providers</strong> — aggregated, anonymized usage data to help us understand how visitors use our site.</li>
</ul>
<p>We may also disclose your information if required by law or to protect the rights and safety of Timber Trace Crafts and our customers.</p>

<h2>Cookies</h2>
<p>We use the following types of cookies:</p>
<ul>
    <li><strong>Essential cookies:</strong> required for the website to function (e.g., shopping cart, login session). These cannot be disabled.</li>
    <li><strong>Analytics cookies:</strong> help us understand how visitors interact with our website. We use anonymized data only.</li>
    <li><strong>Preference cookies:</strong> remember your settings and preferences across visits.</li>
</ul>
<p>You can control or disable cookies through your browser settings. Disabling essential cookies may prevent parts of the site from functioning correctly.</p>

<h2>Data Retention</h2>
<p>We retain your personal information for as long as necessary to fulfill the purposes described in this policy — typically the duration of your customer relationship with us plus up to 7 years to comply with tax and legal record-keeping obligations. You may request deletion of your account and associated data at any time (see Your Rights below).</p>

<h2>Your Rights</h2>
<p>Depending on your location, you may have the following rights regarding your personal information:</p>
<ul>
    <li><strong>Access:</strong> request a copy of the personal information we hold about you.</li>
    <li><strong>Correction:</strong> request that we correct inaccurate information.</li>
    <li><strong>Deletion:</strong> request that we delete your personal information, subject to legal retention requirements.</li>
    <li><strong>Opt-out:</strong> unsubscribe from marketing communications at any time using the link in any email we send.</li>
    <li><strong>California residents (CCPA):</strong> you have the right to know what personal information is collected, to request deletion, and to opt out of the sale of personal information (we do not sell personal information).</li>
</ul>
<p>To exercise any of these rights, contact us at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a>.</p>

<h2>Security</h2>
<p>We take reasonable technical and organizational measures to protect your personal information from unauthorized access, disclosure, or loss. All payment transactions are encrypted via SSL and processed through Stripe's secure infrastructure. However, no method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.</p>

<h2>Children's Privacy</h2>
<p>Our website is not directed to children under 13. We do not knowingly collect personal information from children. If you believe we have inadvertently collected information from a child, please contact us and we will delete it promptly.</p>

<h2>Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time. We will post the revised policy on this page with an updated date. We encourage you to review this page periodically.</p>

<h2>Contact Us</h2>
<p>If you have questions about this Privacy Policy or how we handle your information, please contact us at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a> or through our <a href="/contact">contact form</a>.</p>
HTML,
            ],

            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'meta_title' => 'Terms & Conditions | Timber Trace Crafts',
                'meta_description' => 'Terms and conditions for using the Timber Trace Crafts website and purchasing our products.',
                'body' => <<<'HTML'
<p><em>Last updated: June 22, 2026</em></p>

<p>Please read these Terms & Conditions carefully before using the Timber Trace Crafts website or placing an order. By accessing our website or purchasing from us, you agree to be bound by these terms.</p>

<h2>1. About Us</h2>
<p>Timber Trace Crafts is a handmade woodworking business based in the United States, selling custom and personalized wood goods online at timbertracecrafts.com.</p>

<h2>2. Orders & Acceptance</h2>
<p>All orders are subject to availability and acceptance. We reserve the right to refuse or cancel any order at our discretion, including if we suspect fraud or if an item is out of stock. If we cancel your order, you will receive a full refund to your original payment method.</p>
<p>You will receive an email confirmation when your order is placed. This confirmation is an acknowledgment of your order, not acceptance. Acceptance occurs when your order ships.</p>

<h2>3. Pricing & Payment</h2>
<p>All prices are listed in U.S. dollars and are subject to change without notice. We reserve the right to correct pricing errors. If a pricing error affects your order, we will contact you before processing.</p>
<p>Payment is due in full at the time of purchase. We accept major credit and debit cards, processed securely through Stripe, Inc. By providing payment information, you represent that you are authorized to use that payment method.</p>
<p>Sales tax is collected where required by law based on your shipping address.</p>

<h2>4. Personalized Items</h2>
<p>Personalized items are made to order based on the specifications you provide. Please review all personalization details carefully before submitting your order, as errors in your submitted text cannot be corrected after production begins. Personalized items are non-refundable unless there is a manufacturing defect or the item does not match the specifications you submitted.</p>

<h2>5. Shipping</h2>
<p>Processing and shipping times are detailed in our <a href="/shipping-policy">Shipping Policy</a>. We are not responsible for delays caused by shipping carriers, weather, or circumstances outside our control. Risk of loss passes to you upon delivery to the carrier.</p>

<h2>6. Returns & Refunds</h2>
<p>Our return and refund process is described in our <a href="/return-policy">Return Policy</a>, which is incorporated into these Terms by reference.</p>

<h2>7. Intellectual Property</h2>
<p>All content on this website — including product designs, photographs, text, and graphics — is the property of Timber Trace Crafts and is protected by copyright and other intellectual property laws. You may not reproduce, distribute, or create derivative works from our content without our prior written permission.</p>

<h2>8. User Accounts</h2>
<p>If you create an account, you are responsible for maintaining the confidentiality of your login credentials and for all activity that occurs under your account. Notify us immediately if you believe your account has been compromised.</p>

<h2>9. Prohibited Use</h2>
<p>You agree not to use this website to:</p>
<ul>
    <li>Submit false, fraudulent, or misleading orders or information.</li>
    <li>Infringe on the intellectual property rights of Timber Trace Crafts or any third party.</li>
    <li>Transmit harmful, offensive, or disruptive content.</li>
    <li>Attempt to gain unauthorized access to our systems.</li>
</ul>

<h2>10. Disclaimer of Warranties</h2>
<p>Our products are provided "as is." While we take great care in our craftsmanship, natural wood products may have variations in color, grain, and texture — these are features, not defects. We make no warranties, express or implied, beyond what is stated in our Return Policy.</p>

<h2>11. Limitation of Liability</h2>
<p>To the fullest extent permitted by law, Timber Trace Crafts shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of our website or products, even if we have been advised of the possibility of such damages. Our total liability for any claim arising from a purchase shall not exceed the amount you paid for that purchase.</p>

<h2>12. Promotions & Coupons</h2>
<p>Promotional offers and coupon codes are subject to the specific terms stated at the time of the promotion. Coupons may not be combined unless explicitly stated. We reserve the right to modify or discontinue promotions at any time.</p>

<h2>13. Governing Law</h2>
<p>These Terms & Conditions are governed by the laws of the United States and the state in which Timber Trace Crafts operates, without regard to conflict of law principles. Any disputes shall be resolved in the courts of that jurisdiction.</p>

<h2>14. Changes to These Terms</h2>
<p>We may update these Terms & Conditions from time to time. Continued use of our website after changes are posted constitutes your acceptance of the revised terms. We encourage you to review this page periodically.</p>

<h2>15. Contact Us</h2>
<p>If you have questions about these Terms & Conditions, please contact us at <a href="mailto:hello@timbertracecrafts.com">hello@timbertracecrafts.com</a> or through our <a href="/contact">contact form</a>.</p>
HTML,
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('Policy pages seeded successfully.');
    }
}
