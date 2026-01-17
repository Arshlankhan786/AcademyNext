<?php
require_once 'admin/config/database.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validation
    if (empty($full_name) || empty($email) || empty($message)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO contact_submissions (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full_name, $email, $phone, $subject, $message);

        if ($stmt->execute()) {
            $success = "Thank you for contacting us! We'll get back to you soon.";
            // Clear form
            $full_name = $email = $phone = $subject = $message = '';
        } else {
            $error = "Sorry, something went wrong. Please try again.";
        }
        $stmt->close();
    }
}

// Get course if specified
$course_id = isset($_GET['course']) ? (int) $_GET['course'] : 0;
$course_name = '';
if ($course_id > 0) {
    $course_result = $conn->query("SELECT name FROM courses WHERE id = $course_id");
    if ($course_result->num_rows > 0) {
        $course_name = $course_result->fetch_assoc()['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Next Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="assets/css/public-style.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Page Header -->
    <section class="hero-section" style="min-height: 300px;">
        <div class="container text-center">
            <h1 data-aos="fade-up">Contact Us</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">We'd love to hear from you</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Contact Form -->
                <div class="col-lg-7" data-aos="fade-right">
                    <div class="contact-form">
                        <h3 class="mb-4">Send us a Message</h3>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name"
                                        value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>"
                                        required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" name="email"
                                        value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone"
                                        value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Subject</label>
                                    <input type="text" class="form-control" name="subject"
                                        value="<?php echo $course_name ? 'Enquiry about ' . htmlspecialchars($course_name) : (isset($subject) ? htmlspecialchars($subject) : ''); ?>">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Message *</label>
                                    <textarea class="form-control" name="message" rows="5"
                                        required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-purple btn-lg">
                                        <i class="fas fa-paper-plane"></i> Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Contact Info -->
         <!-- Contact Info -->
            <div class="col-lg-5" data-aos="fade-left">
                <div class="contact-info">
                    <h3 class="mb-4">Get in Touch</h3>
                    
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h5>Address</h5>
                            <p>123 Education Street<br>Ahmedabad, Gujarat 380001<br>India</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h5>Phone</h5>
                            <p>+91 98765 43210<br>+91 98765 43211</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h5>Email</h5>
                            <p>info@nextacademy.com<br>admissions@nextacademy.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h5>Office Hours</h5>
                            <p>Monday - Saturday<br>9:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Follow Us</h5>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-5 bg-light">
    <div class="container" data-aos="fade-up">
        <h3 class="text-center mb-4">Find Us on Map</h3>
        <div class="ratio ratio-21x9">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d235013.74843345142!2d72.41493087263457!3d23.020240775491887!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x395e848aba5bd449%3A0x4fcedd11614f6516!2sAhmedabad%2C%20Gujarat!5e0!3m2!1sen!2sin!4v1234567890123!5m2!1sen!2sin" 
                    style="border:0; border-radius: 15px;" 
                    allowfullscreen="" 
                    loading="lazy"></iframe>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>