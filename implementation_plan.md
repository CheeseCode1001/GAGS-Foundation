# Contact & Donation Forms Email Implementation

This plan outlines the steps to build a fully functional email system that handles both your **Contact Us** form and your **Donation** form. It will forward submissions to your inbox and send automated, beautifully formatted replies to your users, leveraging your existing PHP API and MySQL database.

## User Review Required

> [!IMPORTANT]
> **Google App Password Setup Required**
> Because we are using Option 1 (your existing Gmail), you will need to generate a 16-character App Password from your Google Account settings for `gagsfoundation@gmail.com`. 
> 
> Once I complete the implementation, I will leave a placeholder in the `config.php` file where you must paste this password for the emails to successfully send.

## Proposed Changes

### Dependencies
Since Composer is not installed on your system, we will manually download the standard PHPMailer library and include it directly in your project.
#### [NEW] `php/libs/PHPMailer/`
- Download the PHPMailer source files (`Exception.php`, `PHPMailer.php`, `SMTP.php`) and place them here.

### Database
#### [NEW] `database_setup` (Manual Query Execution)
- I will provide the SQL query to create a `contact_messages` table in your database. This table will serve as a backup to store all inquiries (`name`, `email`, `phone`, `subject`, `message`, `created_at`) in case an email fails to deliver, and to keep a permanent record. *(Note: The donations table should already exist based on previous work).*

### Backend Configurations
#### [MODIFY] `php/config.php`
- Add SMTP configuration constants (Host, Port, Username, Password placeholder) so they can be securely referenced by the email scripts.
- Create a global `sendEmail()` helper function to avoid duplicating PHPMailer code across different routes.

### Backend API (Contact Route)
#### [NEW] `php/routes/contact_routes.php`
- Create a new `POST` endpoint `/api/contact` that will:
  1. Validate and sanitize the form inputs using the helper functions we built previously.
  2. Save the message to the `contact_messages` database table.
  3. Send the notification email to `gagsfoundation@gmail.com` with the user's details.
  4. Send an HTML "Thank You" auto-response to the user's email address.
#### [MODIFY] `api/index.php`
- Register the new `contact_routes.php` file in the main router.

### Backend API (Donation Route)
#### [MODIFY] `php/routes/donations_routes.php`
- Update the existing `POST` endpoint for donations. After the donation details are successfully saved to the database:
  1. Send a notification email to `gagsfoundation@gmail.com` with the donor's details and the amount.
  2. Send an automated "Appreciation" email to the donor thanking them for their generous support.

### Frontend Javascript
#### [MODIFY] `script.js`
- **Contact Form**: Intercept the `submit` event on the `#contact-form` located in `contact.html`. Send the form data to the `/api/contact` endpoint via AJAX, displaying a success/error message to the user without reloading.
- **Donation Form**: Ensure the existing submission logic for the donation form (likely in `script.js` or `donations.html`) cleanly handles the response and shows an appreciation message on the screen.

## HTML Email Styling & Branding

To ensure emails look highly professional and match your company brand (similar to premium corporate emails), we will implement fully customized HTML email templates:

1. **Brand-Aligned Color Palette**:
   - **Background**: Light beige (`#F5F0EB`) matching the website.
   - **Header Banner**: Forest Green (`#1B4332`) header representing GAGS Foundation.
   - **Call-to-Action / Accents**: Terracotta / Warm Orange (`#E76F51`) for visual highlights and links.
   - **Typography**: Clean, standard email-safe sans-serif fonts (Inter fallback stack).

2. **Visual Assets (Logo & Banners)**:
   - We will use **CID (Content-ID) Attachments** in PHPMailer to embed the GAGS Foundation logo inline in the email. This guarantees that the logo loads immediately in the recipient's inbox without asking them to "Download external images."
   - If you have a specific banner image, we can also embed it using the same technique or reference a hosted URL once the site goes live.

3. **Email Templates**:
   - **User Contact Auto-Reply**: A warm greeting card thanking them for contacting GAGS Foundation, summarizing their inquiry, and showing links to current programs.
   - **User Donation Appreciation**: A beautiful "Certificate of Appreciation" style email with donation confirmation details (amount, reference ID), a heartfelt message of impact, and links to see our ongoing projects.
   - **Admin Notification Alert**: A clean, structured receipt card summarizing the inquiry or donation details so you can review them at a glance.

## Verification Plan

### Manual Verification
1. After the code is written, I will instruct you to add your Google App Password to `config.php`.
2. Open your local site, go to the **Contact** page, submit a test message, and verify both the admin notification and the user auto-responder.
3. Go to the **Donations** page, submit a test donation, and verify the admin receives the donation alert and the donor receives the appreciation email.
