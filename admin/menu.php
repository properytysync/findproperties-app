<?php
// admin/menu.php
require_once __DIR__ . "/_auth.php";
require_login();
?>

<nav class="sidebar-main">
    <div class="sidebar-header d-flex justify-content-between align-items-center p-3 border-bottom border-secondary">
        <h5 class="text-white fw-bold mb-0">
            <?= is_admin() ? "Admin Panel" : "Agent Panel" ?>
        </h5>

        <button class="btn btn-outline-light d-md-none" type="button"
                data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
                aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa fa-bars"></i>
        </button>
    </div>

    <div id="sidebarMenu" class="collapse d-md-block sidebar-content">
        <ul class="nav flex-column px-3 py-2">

            <!-- GENERAL -->
            <li class="nav-item mt-3">
                <h6 class="sidebar-section-title">GENERAL</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            </li>

            <!-- USERS -->
            <li class="nav-item mt-3">
                <h6 class="sidebar-section-title">USERS</h6>
            </li>

            <li class="nav-item">
                <button class="nav-link nav-toggle collapsed w-100 text-start"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#userSubmenu"
                        aria-expanded="false"
                        aria-controls="userSubmenu">
                    <i class="fas fa-users me-2"></i> Manage Users
                    <i class="fa fa-chevron-down float-end"></i>
                </button>

                <ul id="userSubmenu" class="collapse list-unstyled">
                    <?php if (is_admin()): ?>
                        <li><a class="nav-link sub-link" href="adminlist.php"><i class="fas fa-user-shield me-2"></i> Admins</a></li>
                    <?php endif; ?>

                    <li><a class="nav-link sub-link" href="agents_list.php"><i class="fas fa-user-tie me-2"></i> Agents</a></li>

                    <?php if (is_admin()): ?>
                        <li><a class="nav-link sub-link" href="add_agent.php"><i class="fas fa-user-plus me-2"></i> Add Agent</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <!-- PROPERTY MANAGEMENT -->
            <li class="nav-item mt-3">
                <h6 class="sidebar-section-title">PROPERTY MANAGEMENT</h6>
            </li>

            <li class="nav-item">
                <button class="nav-link nav-toggle collapsed w-100 text-start"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#propertySubmenu"
                        aria-expanded="false"
                        aria-controls="propertySubmenu">
                    <i class="fas fa-building me-2"></i> Property Listings
                    <i class="fa fa-chevron-down float-end"></i>
                </button>

                <ul id="propertySubmenu" class="collapse list-unstyled">
                    <li><a class="nav-link sub-link" href="propertyadd.php"><i class="fas fa-plus-square me-2"></i> Add Property</a></li>
                    <li><a class="nav-link sub-link" href="propertyview.php"><i class="fas fa-eye me-2"></i> View Properties</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="pplace.php"><i class="fas fa-map-marker-alt me-2"></i> Popular Places</a>
            </li>

            <!-- TENANTS -->
            <li class="nav-item mt-3">
                <h6 class="sidebar-section-title">TENANTS</h6>
            </li>

            <li class="nav-item">
                <button class="nav-link nav-toggle collapsed w-100 text-start"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#tenantSubmenu"
                        aria-expanded="false"
                        aria-controls="tenantSubmenu">
                    <i class="fas fa-user-friends me-2"></i> Tenant Management
                    <i class="fa fa-chevron-down float-end"></i>
                </button>

                <ul id="tenantSubmenu" class="collapse list-unstyled">
                    <li><a class="nav-link sub-link" href="tenant_form.php"><i class="fas fa-user-plus me-2"></i> Add Tenant</a></li>
                    <li><a class="nav-link sub-link" href="display_tenants.php"><i class="fas fa-list me-2"></i> View Tenants</a></li>
                </ul>
            </li>

            <!-- CRM -->
            <li class="nav-item mt-3">
                <h6 class="sidebar-section-title">CRM</h6>
            </li>

            <li class="nav-item">
                <button class="nav-link nav-toggle collapsed w-100 text-start"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#crmSubmenu"
                        aria-expanded="false"
                        aria-controls="crmSubmenu">
                    <i class="fas fa-handshake me-2"></i> CRM Management
                    <i class="fa fa-chevron-down float-end"></i>
                </button>

                <ul id="crmSubmenu" class="collapse list-unstyled">
                    <li><a class="nav-link sub-link" href="crm/index.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a></li>
                    <li><a class="nav-link sub-link" href="crm/leads.php"><i class="fas fa-list me-2"></i> Leads</a></li>

                    <?php if (is_admin()): ?>
                        <li><a class="nav-link sub-link" href="crm/reports.php"><i class="fas fa-file-alt me-2"></i> Reports</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <?php if (is_admin()): ?>
                <!-- PAGE UPDATES -->
                <li class="nav-item mt-4">
                    <h6 class="sidebar-section-title">PAGE UPDATES</h6>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="aboutview.php"><i class="fas fa-info-circle me-2"></i> About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="chatbot/index.php"><i class="fas fa-robot me-2"></i> Chatbot</a>
                </li>

                <!-- INQUIRIES & PAYMENTS -->
                <li class="nav-item mt-3">
                    <h6 class="sidebar-section-title">INQUIRIES & PAYMENTS</h6>
                </li>

                <li class="nav-item">
                    <button class="nav-link nav-toggle collapsed w-100 text-start"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#inquiriesSubmenu"
                            aria-expanded="false"
                            aria-controls="inquiriesSubmenu">
                        <i class="fas fa-envelope-open-text me-2"></i> Inquiries
                        <i class="fa fa-chevron-down float-end"></i>
                    </button>

                    <ul id="inquiriesSubmenu" class="collapse list-unstyled">
                        <li><a class="nav-link sub-link" href="contactview.php"><i class="fas fa-comments me-2"></i> Contact Messages</a></li>
                        <li><a class="nav-link sub-link" href="scheduleview.php"><i class="fas fa-money-check-alt me-2"></i> View Payments</a></li>
                        <li><a class="nav-link sub-link" href="schedulepay.php"><i class="fas fa-toggle-on me-2"></i> Activate Payments</a></li>
                    </ul>
                </li>

                <!-- SETTINGS -->
                <li class="nav-item mt-4">
                    <h6 class="sidebar-section-title">SETTINGS</h6>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="seo.php"><i class="fas fa-search me-2"></i> SEO</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_menu.php"><i class="fas fa-cogs me-2"></i> Menu Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php"><i class="fas fa-tools me-2"></i> Site Settings</a>
                </li>
            <?php endif; ?>

            <!-- ACCOUNT -->
            <li class="nav-item mt-4">
                <h6 class="sidebar-section-title">ACCOUNT</h6>
            </li>

            <?php if (is_agent()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="edit_agent.php?id=<?= (int)$_SESSION['user_id'] ?>">
                        <i class="fas fa-user-cog me-2"></i> My Profile
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item mb-3">
                <a class="nav-link text-danger fw-semibold" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>

        </ul>
    </div>
</nav>

<style>
.sidebar-main{position:fixed;top:0;left:0;width:260px;height:100vh;background:#1c1f23;overflow-y:auto;z-index:1050}
.sidebar-header{background:#212529}
.sidebar-content{max-height:calc(100vh - 60px);overflow-y:auto;padding-bottom:30px}
.sidebar-main,.sidebar-content,.sidebar-content ul,.sidebar-content li,.sidebar-content a,.sidebar-content button{text-align:left!important}
.sidebar-section-title{color:#adb5bd;font-size:.75rem;font-weight:600;letter-spacing:.5px}
.nav-link{color:#e9ecef!important;font-size:.9rem;display:block;width:100%;padding:8px 10px;border-radius:6px;transition:all .2s ease;background:transparent;border:0}
.nav-link:hover{background:rgba(255,255,255,.15);color:#fff!important;text-decoration:none}
.nav-link.sub-link{font-size:.85rem;margin-left:10px}
button.nav-link.nav-toggle{cursor:pointer}
</style>

<!-- Bootstrap Bundle for dropdown collapse -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.nav-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setTimeout(() => {
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                btn.classList.toggle('is-open', expanded);
            }, 150);
        });
    });
});
</script>