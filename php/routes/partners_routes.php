<?php
/**
 * GAGS Foundation - Partners Routes
 * 
 * POST   /api/partners      — Submit partnership application (public)
 * GET    /api/partners       — List all partner applications (auth required)
 * DELETE /api/partners/:id   — Delete partner application (auth required)
 */

function handlePartnersRoute($method, $id) {
    switch ($method) {
        case 'POST':
            createPartner();
            break;
        case 'GET':
            requireAuth();
            getPartners();
            break;
        case 'DELETE':
            requireAuth();
            if (!$id) jsonError('Partner ID required', 400);
            deletePartner($id);
            break;
        default:
            jsonError('Method Not Allowed', 405);
    }
}

// ============ POST /api/partners ============
function createPartner() {
    try {
        $data = getJsonBody();
        
        $orgName = sanitizeString($data['org_name'] ?? '', 255);
        $email = validateEmail($data['email'] ?? '');
        
        if (empty($orgName) || empty($email)) {
            jsonError('Valid organization name and email required', 400);
        }
        
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO partners (org_name, contact_name, email, phone, partnership_type, message) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $orgName,
            sanitizeString($data['contact_name'] ?? '', 255),
            $email,
            sanitizeString($data['phone'] ?? '', 50),
            sanitizeString($data['partnership_type'] ?? '', 100),
            sanitizeString($data['message'] ?? '', 2000),
        ]);
        
        $partnerId = (int)$pdo->lastInsertId();

        $contactName = sanitizeString($data['contact_name'] ?? '', 255);
        $phone = sanitizeString($data['phone'] ?? '', 50);
        $partnershipType = sanitizeString($data['partnership_type'] ?? '', 100);
        $message = sanitizeString($data['message'] ?? '', 2000);

        // Send Notification to Admin
        $adminSubject = "New Partnership Application from " . $orgName;
        $adminHtml = "
        <!DOCTYPE html>
        <html>
        <head><meta charset='utf-8'></head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #F5F0EB; color: #4a4a4a;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; margin: 30px auto; background-color: #ffffff; border: 1px solid #EDE8E2; border-collapse: collapse;'>
                <tr>
                    <td align='center' bgcolor='#1B4332' style='padding: 20px; color: #ffffff;'>
                        <h1 style='margin: 0; font-size: 20px; font-weight: bold;'>New Partnership Alert</h1>
                    </td>
                </tr>
                <tr>
                    <td style='padding: 30px;'>
                        <p style='margin: 0 0 20px 0; font-size: 16px;'>You have received a new partnership application:</p>
                        <table border='0' cellpadding='8' cellspacing='0' width='100%' style='border-collapse: collapse; margin-bottom: 20px;'>
                            <tr style='background-color: #F5F0EB;'>
                                <td width='30%' style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Organization:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>" . htmlspecialchars($orgName) . "</td>
                            </tr>
                            <tr>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Contact Name:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>" . htmlspecialchars($contactName) . "</td>
                            </tr>
                            <tr style='background-color: #F5F0EB;'>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Email:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'><a href='mailto:" . htmlspecialchars($email) . "' style='color: #E76F51; text-decoration: none;'>" . htmlspecialchars($email) . "</a></td>
                            </tr>
                            <tr>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Phone:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>" . htmlspecialchars($phone) . "</td>
                            </tr>
                            <tr style='background-color: #F5F0EB;'>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Type:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>" . htmlspecialchars(ucfirst($partnershipType)) . "</td>
                            </tr>
                            <tr>
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

        // Prepare attachments for user email
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

        // Send Auto-Reply to Partner
        $userSubject = "Partnership Application Received - GAGS Foundation";
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
                    </td>
                </tr>
                <tr>
                    <td style='padding: 40px 30px; text-align: left;'>
                        <h2 style='margin: 0 0 20px 0; color: #E76F51; font-size: 22px;'>Thank You For Your Interest!</h2>
                        <p style='margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;'>
                            Dear " . htmlspecialchars($contactName ?: $orgName) . ",<br><br>
                            Thank you for submitting a partnership application to GAGS Foundation. We are thrilled that you are interested in working with us to empower our communities and transform lives.
                        </p>
                        <p style='margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;'>
                            Our team will review your application and get back to you within 7 business days to discuss potential opportunities.
                        </p>
                        <p style='margin: 0 0 30px 0; font-size: 16px; line-height: 1.6;'>
                            In the meantime, feel free to explore our ongoing projects and programs on our website.
                        </p>
                        <table align='center' border='0' cellpadding='0' cellspacing='0' style='margin: 0 auto 30px auto;'>
                            <tr>
                                <td align='center' bgcolor='#1B4332'>
                                    <a href='https://gagsfoundation.org/about.html' target='_blank' style='display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none;'>Learn More About Us</a>
                                </td>
                            </tr>
                        </table>
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

        jsonResponse(['success' => true, 'id' => $partnerId]);
    } catch (PDOException $e) {
        error_log('Create partner error: ' . $e->getMessage());
        jsonError('Failed to submit partnership application', 500);
    }
}

// ============ GET /api/partners ============
function getPartners() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT * FROM partners ORDER BY created_at DESC');
        $partners = $stmt->fetchAll();
        jsonResponse($partners);
    } catch (PDOException $e) {
        error_log('Get partners error: ' . $e->getMessage());
        jsonError('Failed to fetch partners', 500);
    }
}

// ============ DELETE /api/partners/:id ============
function deletePartner($id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM partners WHERE id = ?');
        $stmt->execute([(int)$id]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Delete partner error: ' . $e->getMessage());
        jsonError('Failed to delete partner', 500);
    }
}
