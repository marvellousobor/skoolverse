<style>
/* ═══════════════════ FOOTER ═══════════════════ */

/* Push footer below sidebar on desktop */
.site-footer {
    margin-left: 280px;
    background: linear-gradient(180deg, #0f172a 0%, #1e3a8a 50%, #0f172a 100%);
    color: #ffffff;
    position: relative;
    overflow: hidden; /* keeps any decorative elements INSIDE */
}

/* Top accent line */
.footer-accent-line {
    height: 3px;
    background: linear-gradient(90deg, transparent, #3b82f6, transparent);
    width: 100%;
}

.footer-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 3rem 2rem 2rem;
}

/* ── Grid ── */
.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr;
    gap: 2.5rem;
    margin-bottom: 2.5rem;
}

/* ── Brand column ── */
.footer-brand-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.footer-brand-logo-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6, #1e40af);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.footer-brand-logo-icon svg {
    width: 20px;
    height: 20px;
    fill: #ffffff;
}

.footer-brand-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #ffffff;
    line-height: 1.2;
}

.footer-brand-sub {
    font-size: 0.75rem;
    color: #93c5fd;
}

.footer-brand-desc {
    font-size: 0.875rem;
    color: #bfdbfe;
    line-height: 1.6;
    margin-bottom: 1.25rem;
}

/* ── Column headings ── */
.footer-col-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.footer-col-title::before {
    content: '';
    display: block;
    width: 18px;
    height: 3px;
    background: #3b82f6;
    border-radius: 2px;
    flex-shrink: 0;
}

/* ── Links ── */
.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.footer-links li a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #bfdbfe;
    text-decoration: none;
    transition: color 0.2s ease, padding-left 0.2s ease;
}

.footer-links li a:hover {
    color: #ffffff;
    padding-left: 4px;
}

.footer-links li a i {
    font-size: 0.75rem;
    color: #60a5fa;
    width: 14px;
}

/* ── Contact column ── */
.footer-contact-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #93c5fd;
    margin-bottom: 0.35rem;
}

.footer-contact-value {
    font-size: 0.875rem;
    color: #ffffff;
    font-weight: 500;
    text-decoration: none;
    display: block;
    margin-bottom: 1.25rem;
    transition: color 0.2s;
}

.footer-contact-value:hover {
    color: #93c5fd;
}

/* Social icons */
.footer-socials {
    display: flex;
    gap: 0.6rem;
    margin-top: 0.25rem;
}

.footer-social-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(59, 130, 246, 0.25);
    border: 1px solid rgba(59, 130, 246, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #93c5fd;
    transition: all 0.2s ease;
    text-decoration: none;
}

.footer-social-btn:hover {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #ffffff;
    transform: translateY(-2px);
}

.footer-social-btn i {
    font-size: 0.8rem;
}

/* ── CTA Banner ── */
.footer-cta {
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    border: 1px solid rgba(96, 165, 250, 0.3);
    border-radius: 12px;
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 2.5rem;
    flex-wrap: wrap;
}

.footer-cta-text h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 0.25rem 0;
}

.footer-cta-text p {
    font-size: 0.85rem;
    color: #bfdbfe;
    margin: 0;
}

.footer-cta-btn {
    padding: 0.6rem 1.5rem;
    background: #ffffff;
    color: #1d4ed8;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-family: 'Segoe UI', sans-serif;
    text-decoration: none;
}

.footer-cta-btn:hover {
    background: #eff6ff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* ── Bottom bar ── */
.footer-bottom-line {
    height: 1px;
    background: rgba(59, 130, 246, 0.25);
    margin-bottom: 1.5rem;
}

.footer-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-copyright {
    font-size: 0.8rem;
    color: #93c5fd;
}

.footer-bottom-links {
    display: flex;
    gap: 1.25rem;
}

.footer-bottom-links a {
    font-size: 0.8rem;
    color: #93c5fd;
    text-decoration: none;
    transition: color 0.2s;
}

.footer-bottom-links a:hover {
    color: #ffffff;
}

.footer-heart {
    font-size: 0.8rem;
    color: #93c5fd;
    text-align: right;
}

.footer-heart span {
    color: #f87171;
}

/* ═══════════════════ RESPONSIVE ═══════════════════ */
@media (max-width: 1024px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
}

@media (max-width: 768px) {
    /* On mobile the sidebar is hidden, so no left offset */
    .site-footer {
        margin-left: 0;
    }

    .footer-inner {
        padding: 2rem 1.25rem 1.5rem;
    }

    .footer-grid {
        grid-template-columns: 1fr;
        gap: 1.75rem;
    }

    .footer-cta {
        flex-direction: column;
        text-align: center;
        padding: 1.25rem;
    }

    .footer-bottom {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.75rem;
    }

    .footer-heart {
        text-align: center;
    }
}
</style>

<footer class="site-footer">

    <!-- Top accent -->
    <div class="footer-accent-line"></div>

    <div class="footer-inner">

        <!-- ── Main grid ── -->
        <div class="footer-grid">

            <!-- Brand -->
            <div>
                <div class="footer-brand-logo">
                    <div class="footer-brand-logo-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="footer-brand-name">SPMS</div>
                        <div class="footer-brand-sub">School Portal</div>
                    </div>
                </div>
                <p class="footer-brand-desc">
                    A complete school management solution designed for modern educational institutions — streamlining academics, finance and communication.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="footer-col-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Students</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Results</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Fees &amp; Payments</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Reports</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <h4 class="footer-col-title">Support</h4>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-circle-question"></i> Help Center</a></li>
                    <li><a href="#"><i class="fas fa-comments"></i> Contact Us</a></li>
                    <li><a href="#"><i class="fas fa-list-ul"></i> FAQs</a></li>
                    <li><a href="#"><i class="fas fa-circle-check"></i> System Status</a></li>
                    <li><a href="#"><i class="fas fa-book"></i> Documentation</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="footer-col-title">Get in Touch</h4>

                <div class="footer-contact-label">Email</div>
                <a href="mailto:support@spms.com" class="footer-contact-value">support@spms.com</a>

                <div class="footer-contact-label">Phone</div>
                <a href="tel:+2348000000000" class="footer-contact-value">+234 800 000 0000</a>

                <div class="footer-contact-label">Follow Us</div>
                <div class="footer-socials">
                    <a href="#" class="footer-social-btn" title="Twitter / X">
                        <i class="fab fa-x-twitter"></i>
                    </a>
                    <a href="#" class="footer-social-btn" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="footer-social-btn" title="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="footer-social-btn" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- ── CTA Banner ── -->
        <div class="footer-cta">
            <div class="footer-cta-text">
                <h3><i class="fas fa-headset"></i> Need Assistance?</h3>
                <p>Our support team is available and ready to help you.</p>
            </div>
            <a href="mailto:support@spms.com" class="footer-cta-btn">
                <i class="fas fa-envelope"></i>
                Contact Support
            </a>
        </div>

        <!-- ── Bottom bar ── -->
        <div class="footer-bottom-line"></div>

        <div class="footer-bottom">
            <p class="footer-copyright">
                &copy; 2026 SPMS &mdash; School Portal Management System. All rights reserved.
            </p>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Use</a>
                <a href="#">Sitemap</a>
            </div>
            <p class="footer-heart">
                Built with <span>&#9829;</span> for educators &mdash; v2.1.0
            </p>
        </div>

    </div>
</footer>