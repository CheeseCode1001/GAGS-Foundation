<?php
/**
 * GAGS Foundation - Donations Routes
 * 
 * GET    /api/donations      — List all donations (auth required)
 * POST   /api/donations      — Create donation (public)
 * DELETE /api/donations/:id  — Delete donation (auth required)
 */

function handleDonationsRoute($method, $id) {
    switch ($method) {
        case 'GET':
            requireAuth();
            getDonations();
            break;
        case 'POST':
            createDonation();
            break;
        case 'DELETE':
            requireAuth();
            if (!$id) jsonError('Donation ID required', 400);
            deleteDonation($id);
            break;
        default:
            jsonError('Method Not Allowed', 405);
    }
}

// ============ GET /api/donations ============
function getDonations() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT * FROM donations ORDER BY created_at DESC');
        $donations = $stmt->fetchAll();
        jsonResponse($donations);
    } catch (PDOException $e) {
        error_log('Get donations error: ' . $e->getMessage());
        jsonError('Failed to fetch donations', 500);
    }
}

// ============ POST /api/donations ============
function createDonation() {
    try {
        $data = getJsonBody();
        
        $donorName = sanitizeString($data['donor_name'] ?? '', 255);
        $email = validateEmail($data['email'] ?? '');
        $amount = (float)($data['amount'] ?? 0);
        
        if (empty($donorName) || empty($email) || $amount <= 0) {
            jsonError('Valid name, email, and positive amount are required', 400);
        }
        
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO donations (donor_name, email, amount, project_id, message) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $donorName,
            $email,
            $amount,
            !empty($data['project_id']) ? (int)$data['project_id'] : null,
            sanitizeString($data['message'] ?? '', 1000),
        ]);
        
        $donationId = (int)$pdo->lastInsertId();

        // Send Notification to Admin
        $adminSubject = "New Donation Received: $" . number_format($amount, 2);
        $adminHtml = "
        <!DOCTYPE html>
        <html>
        <head><meta charset='utf-8'></head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #F5F0EB; color: #4a4a4a;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; margin: 30px auto; background-color: #ffffff; border: 1px solid #EDE8E2; border-collapse: collapse;'>
                <tr>
                    <td align='center' bgcolor='#1B4332' style='padding: 20px; color: #ffffff;'>
                        <h1 style='margin: 0; font-size: 20px; font-weight: bold;'>New Donation Alert</h1>
                    </td>
                </tr>
                <tr>
                    <td style='padding: 30px;'>
                        <p style='margin: 0 0 20px 0; font-size: 16px;'>You have received a new donation:</p>
                        <table border='0' cellpadding='8' cellspacing='0' width='100%' style='border-collapse: collapse; margin-bottom: 20px;'>
                            <tr style='background-color: #F5F0EB;'>
                                <td width='30%' style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Donor Name:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>" . htmlspecialchars($donorName) . "</td>
                            </tr>
                            <tr>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Email:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'><a href='mailto:" . htmlspecialchars($email) . "' style='color: #E76F51; text-decoration: none;'>" . htmlspecialchars($email) . "</a></td>
                            </tr>
                            <tr style='background-color: #F5F0EB;'>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Amount:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>$" . number_format($amount, 2) . "</td>
                            </tr>
                            <tr>
                                <td style='font-weight: bold; border-bottom: 1px solid #EDE8E2;'>Message:</td>
                                <td style='border-bottom: 1px solid #EDE8E2;'>" . htmlspecialchars($data['message'] ?? '') . "</td>
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

        // Send Thank You to Donor
        $userSubject = "Certificate of Appreciation - GAGS Foundation";
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
                    <td style='padding: 40px 30px; text-align: center;'>
                        <h2 style='margin: 0 0 20px 0; color: #E76F51; font-size: 22px;'>Thank You For Your Generosity!</h2>
                        <p style='margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; text-align: left;'>
                            Dear " . htmlspecialchars($donorName) . ",<br><br>
                            We deeply appreciate your generous donation of <strong>$" . number_format($amount, 2) . "</strong>. Your contribution plays a crucial role in empowering our communities and transforming lives.
                        </p>
                        <p style='margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; text-align: left;'>
                            With your support, we can continue to build a stronger foundation for those who need it most.
                        </p>
                        <table align='center' border='0' cellpadding='0' cellspacing='0' style='margin: 0 auto 30px auto;'>
                            <tr>
                                <td align='center' bgcolor='#1B4332'>
                                    <a href='https://gagsfoundation.org/projects.html' target='_blank' style='display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none;'>See Your Impact</a>
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

        jsonResponse(['success' => true, 'id' => $donationId]);
    } catch (Exception $e) {
        error_log('Create donation error: ' . $e->getMessage());
        jsonError('Failed to create donation', 500);
    }
}

// ============ DELETE /api/donations/:id ============
function deleteDonation($id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM donations WHERE id = ?');
        $stmt->execute([(int)$id]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Delete donation error: ' . $e->getMessage());
        jsonError('Failed to delete donation', 500);
    }
}
