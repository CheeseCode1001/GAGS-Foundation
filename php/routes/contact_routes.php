<?php
/**
 * GAGS Foundation - Contact Routes
 * 
 * POST   /api/contact       — Submit contact inquiry (public)
 * GET    /api/contact       — List all contact inquiries (auth required)
 * DELETE /api/contact/:id   — Delete contact inquiry (auth required)
 */

function handleContactRoute($method, $id) {
    switch ($method) {
        case 'POST':
            submitContact();
            break;
        case 'GET':
            requireAuth();
            getContacts();
            break;
        case 'DELETE':
            requireAuth();
            if (!$id) jsonError('Contact ID required', 400);
            deleteContact($id);
            break;
        default:
            jsonError('Method Not Allowed', 405);
    }
}

// ============ POST /api/contact ============
function submitContact() {
    try {
        $data = getJsonBody();
        
        $name = sanitizeString($data['name'] ?? '', 255);
        $email = validateEmail($data['email'] ?? '');
        $phone = sanitizeString($data['phone'] ?? '', 50);
        $subject = sanitizeString($data['subject'] ?? '', 255);
        $message = sanitizeString($data['message'] ?? '', 3000);
        
        if (empty($name) || empty($email) || empty($message)) {
            jsonError('Name, valid email, and message are required', 400);
        }
        
        // 1. Save to Database
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $phone, $subject, $message]);
        
        $contactId = (int)$pdo->lastInsertId();

        // 2. Prepare visual asset attachments for the beautiful auto-reply
        $attachments = [];
        $logoPath = dirname(__DIR__, 2) . '/assets/images/logo.png';
        if (file_exists($logoPath)) {
            $attachments[] = [
                'path' => $logoPath,
                'name' => 'logo.png',
                'cid' => 'logo',
                'disposition' => 'inline',
                'type' => 'image/png'
            ];
        }


        // 3. Send Notification to Admin (gagsfoundation@gmail.com)
        $adminSubject = "New Contact Inquiry from " . $name;
        $adminHtml = "
        <!DOCTYPE html>
        <html>
        <head><meta charset='utf-8'></head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #F5F0EB; color: #4a4a4a;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; margin: 30px auto; background-color: #ffffff; border: 1px solid #EDE8E2; border-collapse: collapse;'>
                <tr>
                    <td align='center' bgcolor='#1B4332' style='padding: 20px; color: #ffffff;'>
                        <h1 style='margin: 0; font-size: 20px; font-weight: bold;'>New Contact Submission</h1>
                    </td>
                </tr>
                <tr>
                    <td style='padding: 30px;'>
                        <p style='margin: 0 0 20px 0; font-size: 16px;'>You have received a new message through the website contact form:</p>
                        <table border='0' cellpadding='8' cellspacing='0' width='100%' style='border-collapse: collapse; margin-bottom: 20px;'>
                            <tr style='background-color: #F5F0EB;'>
                                <td width='30%' style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Name:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>" . htmlspecialchars($name) . "</td>
                            </tr>
                            <tr>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Email:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'><a href='mailto:" . htmlspecialchars($email) . "' style='color: #E76F51; text-decoration: none;'>" . htmlspecialchars($email) . "</a></td>
                            </tr>
                            <tr style='background-color: #F5F0EB;'>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Phone:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>" . htmlspecialchars($phone) . "</td>
                            </tr>
                            <tr>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Subject:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>" . htmlspecialchars($subject) . "</td>
                            </tr>
                            <tr style='background-color: #F5F0EB;'>
                                <td valign='top' style='font-weight: bold;'>Message:</td>
                                <td>" . nl2br(htmlspecialchars($message)) . "</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
        
        sendEmail(SMTP_USER, $adminSubject, $adminHtml);

        // 4. Send Thank You Auto-Reply to User
        $userSubject = "Thank you for contacting GAGS Foundation";
        $userHtml = "
        <!DOCTYPE html>
        <html>
        <head><meta charset='utf-8'></head>
        <body style='margin: 0; padding: 0; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; background-color: #F5F0EB; color: #4a4a4a;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; margin: 30px auto; background-color: #ffffff; border: 1px solid #EDE8E2; border-collapse: collapse;'>
                <tr>
                    <td align='center' bgcolor='#1B4332' style='padding: 40px 20px; color: #ffffff;'>
                        <img src='cid:logo' alt='GAGS Foundation Logo' style='display: block; width: 60px; height: 60px; margin-bottom: 15px;'>
                        <h1 style='margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.02em;'>GAGS FOUNDATION</h1>
                        <p style='margin: 5px 0 0 0; font-size: 14px; color: #B7E4C7; letter-spacing: 1px; text-transform: uppercase;'>Empowering Communities, Transforming Lives</p>
                    </td>
                </tr>

                <tr>
                    <td style='padding: 40px 30px;'>
                        <h2 style='margin: 0 0 20px 0; color: #1B4332; font-size: 20px; font-weight: 700; border-bottom: 2px solid #EDE8E2; padding-bottom: 10px;'>Dear " . htmlspecialchars($name) . ",</h2>
                        <p style='margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;'>
                            Thank you for reaching out to us! We have successfully received your inquiry and wanted to let you know that our team is looking into it. We will get back to you as soon as possible (usually within 24 to 48 hours).
                        </p>
                        <p style='margin: 0 0 30px 0; font-size: 16px; line-height: 1.6;'>
                            While we review your request, feel free to browse our ongoing programs and see how we are building a stronger foundation for the community.
                        </p>
                        <table align='center' border='0' cellpadding='0' cellspacing='0' style='margin: 0 auto 30px auto;'>
                            <tr>
                                <td align='center' bgcolor='#E76F51'>
                                    <a href='https://gagsfoundation.org/programs.html' target='_blank' style='display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none;'>Explore Our Programs</a>
                                </td>
                            </tr>
                        </table>
                        <div style='background-color: #F5F0EB; padding: 20px; border-left: 4px solid #1B4332; margin-bottom: 20px;'>
                            <h3 style='margin: 0 0 10px 0; color: #1B4332; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>Summary of your message:</h3>
                            <p style='margin: 0 0 8px 0; font-size: 14px; line-height: 1.5;'><strong>Subject:</strong> " . htmlspecialchars($subject ?: 'General Inquiry') . "</p>
                            <p style='margin: 0; font-size: 14px; line-height: 1.5; font-style: italic;'>\"" . htmlspecialchars($message) . "\"</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td bgcolor='#1B4332' style='padding: 30px; text-align: center; color: rgba(255,255,255,0.7); font-size: 12px; line-height: 1.6;'>
                        <p style='margin: 0 0 10px 0; color: #ffffff; font-weight: 600; font-size: 14px;'>GAGS Foundation</p>
                        <p style='margin: 0 0 15px 0;'>Contact Us: info@gagsfoundation.org</p>
                        <p style='margin: 0;'>&copy; 2026 GAGS Foundation. All rights reserved.</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>";

        sendEmail($email, $userSubject, $userHtml, '', $attachments);

        jsonResponse(['success' => true, 'id' => $contactId]);
    } catch (PDOException $e) {
        error_log('Submit contact error: ' . $e->getMessage());
        jsonError('Failed to process message', 500);
    }
}

// ============ GET /api/contact ============
function getContacts() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC');
        $contacts = $stmt->fetchAll();
        jsonResponse($contacts);
    } catch (PDOException $e) {
        error_log('Get contacts error: ' . $e->getMessage());
        jsonError('Failed to fetch messages', 500);
    }
}

// ============ DELETE /api/contact/:id ============
function deleteContact($id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = ?');
        $stmt->execute([(int)$id]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Delete contact error: ' . $e->getMessage());
        jsonError('Failed to delete message', 500);
    }
}
