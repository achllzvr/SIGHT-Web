<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script defer src="{{ asset('assets/js/alpine.min.js') }}"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: 
                radial-gradient(circle at 90% 50%, rgba(42, 131, 68, 0.2) 0%, transparent 35%),
                radial-gradient(circle at 50% 50%, #E4FFD8 0%, transparent 60%),
                radial-gradient(circle at 15% 20%, rgba(251, 207, 232, 0.6) 0%, transparent 20%);
            color: #1f2937;
            position: relative;
            overflow: hidden;
        }

        .bg-lumi-text {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 30vw;
            font-weight: 900;
            font-family: 'Fredoka', sans-serif;
            color: #E4FFD8;
            z-index: -1;
            letter-spacing: 25px;
            user-select: none;
            transition: all 0.6s ease;
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.2);
            text-shadow: 
                5px 15px 30px rgba(0, 0, 0, 0.05),
                -1px -1px 0 rgba(255, 255, 255, 0.4);
            pointer-events: none;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 60px;
        }

        .tabs {
            position: relative;
            display: flex;
            justify-content: left;
            width: 100%;
            gap: 5px;
            margin-bottom: 30px;
            background: white;
            padding: 10px;
            border-radius: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .tab-indicator {
            position: absolute;
            background: #527267;
            border-radius: 24px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 0;
        }

        .tab {
            position: relative;
            z-index: 1;
            padding: 10px 24px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #9ca3af;
            border: none;
            background: transparent;
            border-radius: 24px;
            transition: color 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab.active {
            color: white;
        }

        .tab:hover {
            color: #1f2937;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
            height: calc(100vh - 160px); 
            overflow-y: auto;
            padding-bottom: 2rem;
            padding-right: 10px;
        }
        
        /* Optional: Sleek scrollbar for the tab content */
        .content-section::-webkit-scrollbar { width: 6px; }
        .content-section::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px 24px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
        }

        .professionals-panel {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
        }

        .toolbar {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            padding: 10px 16px;
            border: 1px solid #66736d;
            border-radius: 28px;
            font-size: 14px;
            outline: none;
        }

        .search-box:focus {
            border-color: #295c4a;
        }

        .add-button {
            background: #81A398;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            transition: background 0.2s;
            margin-left: auto;
        
        }

        .add-button:hover {
            background: #16a34a;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 18px;
            border: none;
            border-radius: 16px;
           
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
            background: #f7faf9;
            color: #527267;
            border: 1px solid #527267;
        }

        .filter-btn.active {
            background: #527267;
            color: white;
        }

        .filter-btn:hover {
            opacity: 0.8;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        tbody tr:hover {
            background: #fafbfc;
        }

        .professional-name {
            font-weight: 700;
            color: #1f2937;
            font-size: 15px;
        }

        .professional-specialty {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 2px;
        }

        .professional-email {
            color: #6b7280;
            font-size: 13px;
        }

        .professional-phone {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 2px;
        }

        .clinic-info {
            font-size: 13px;
        }

        .clinic-name {
            color: #1f2937;
            font-weight: 500;
        }

        .clinic-license {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 2px;
        }

        .last-active-time {
            color: #1f2937;
            font-weight: 500;
            font-size: 13px;
        }

        .joined-date {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 2px;
        }

        .patient-badge {
            background: #527267;
            border-radius: 15px;
            padding: 8px 15px;
            font-weight: 600;
            font-size: 13px;
            color: #fff;
        }

        .status-badge {
            border-radius: 15px;
            padding: 8px 11px;
            font-size: 13px;
            display: inline-block;
        }

        .status-active {
            background: #E5FFF6;
            color: #7BC680;
            border: 1px solid #7BC680;
        }

        .status-inactive {
            background: #e5e7eb;
            color: #6b7280;
        }

        .status-suspended {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-pending {
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #854d0e;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .action-btn {
            background: none;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: color 0.2s;
        }

        .action-btn.edit {
            color: #295c4a;
        }

        .action-btn.edit:hover {
            color: #1f2937;
        }

        .action-btn.delete {
            color: #ef4444;
        }

        .action-btn.delete:hover {
            color: #dc2626;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 50;
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal {
            background: white;
            border-radius: 12px;
            padding: 24px;
            min-width: 500px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 32px rgba(0,0,0,0.15);
        }

        .modal-header {
            margin-bottom: 16px;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }

        .modal-body {
            margin-bottom: 16px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .form-grid.edit {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 9999px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
            background: #ffffff;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #295c4a;
        }

        .modal-footer {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .modal-footer button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #22c55e;
            color: white;
        }

        .btn-primary:hover {
            background: #16a34a;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .btn-save {
            background: #295c4a;
            color: white;
        }

        .btn-save:hover {
            background: #1f3a34;
        }

        .settings-form {
            background: transparent;
            padding: 0;
        }

        .form-section {
            background: #FCFFFD;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            margin-bottom: 24px;
            display: flex;
            gap: 20px;
        }

        .form-section::before {
            content: '';
            min-width: 50px;
            width: 50px;
            height: 50px;
            background: #527267;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .form-section h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #1f2937;
        }

        .form-section > div {
            flex: 1;
        }

        .section-subtitle {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: #374151;
            margin-bottom: 8px;
        }

        .checkbox-label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #1f2937;
            margin: 0;
            margin-bottom: 4px;
        }

        .checkbox-description {
            font-size: 12px;
            color: #9ca3af;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 9999px;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
        }

        input:focus {
            border-color: #295c4a;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: #F0FAF8;
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .checkbox-info {
            flex: 1;
        }

        input[type="checkbox"] {
            display: none;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 28px;
            background: #d1d5db;
            border-radius: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: left 0.3s;
        }

        input[type="checkbox"]:checked + .toggle-switch {
            background: #22c55e;
        }

        input[type="checkbox"]:checked + .toggle-switch::after {
            left: 24px;
        }

        .admin-list {
            margin-top: 15px;
        }

        .admin-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .admin-item:last-child {
            border-bottom: none;
        }

        .admin-email {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        .admin-status {
            background: #dcfce7;
            color: #166534;
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
        }

        .admin-action {
            color: #ef4444;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            line-height: 1;
            background: none;
            border: none;
            padding: 0;
            transition: opacity 0.2s;
        }

        [x-cloak] { display: none !important; }

        /* Verification Slider Styling */
        .verification-slider {
            position: relative;
            display: inline-block;
            width: 38px;
            height: 20px;
        }

        .verification-slider input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .v-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #cbd5e1;
            transition: .3s;
            border-radius: 20px;
        }

        .v-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background: white;
            transition: .3s;
            border-radius: 50%;
        }

        input:checked + .v-slider {
            background: #527267;
        }

        input:checked + .v-slider:before {
            transform: translateX(18px);
        }

        .admin-action:hover {
            opacity: 0.7;
        }

        .save-button {
            background: #527267;
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-top: 20px;
            transition: background 0.2s;
            display: block;
            margin-left: auto;
            margin-right: 0;
        }

        .save-button:hover {
            background: #3d5a55;
        }

        .add-admin-section {
            text-align: right;
            margin-bottom: 20px;
        }

        .add-admin-btn {
            background: #527267;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .add-admin-btn:hover {
            background: #3d5a55;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 380px;
        }

        .toast {
            border-radius: 10px;
            padding: 12px 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            color: #fff;
            font-size: 13px;
            line-height: 1.4;
            animation: toastIn .25s ease-out;
        }

        .toast.success { background: #15803d; }
        .toast.error { background: #b91c1c; }
        .toast.info { background: #1d4ed8; }

        @keyframes toastIn {
            from { transform: translateY(-8px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 1024px) {
            .form-grid,
            .form-row {
                grid-template-columns: 1fr 1fr;
            }

            .modal {
                min-width: 95%;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: unset;
            }

            .form-grid,
            .form-row {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            td, th {
                padding: 8px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 6px;
            }

            .modal {
                min-width: 95%;
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-lumi-text">LUMI</div>
    <div class="container" x-data="professionalsManager()">
        <div class="toast-container" x-cloak>
            <template x-for="toast in toasts" :key="toast.id">
                <div class="toast" :class="toast.type" x-text="toast.message"></div>
            </template>
        </div>

        <div class="tabs">
            <div id="tab-indicator" class="tab-indicator"></div>
            <div class="tab active" onclick="showSection(this, 'professionals')">Professionals</div>
            <div class="tab" onclick="showSection(this, 'settings')">Settings</div>
            <div class="tab" onclick="showSection(this, 'audit-logs')">Audit Logs</div>
            <form action="{{ route('logout') }}" method="POST" style="margin: 0; margin-left: auto;">
                @csrf
                <button type="submit" class="tab" style="color: #ef4444; cursor: pointer;">Logout</button>
            </form>
        </div>

        <!-- Professionals Section -->
        <div id="professionals" class="content-section active">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card" style="background-color: #4B6059;">
                    <div class="stat-label" style="color: rgba(255, 255, 255, 0.8);">Total Professionals</div>
                    <div class="stat-value" style="color: white;" x-text="stats.total_professionals || 0">{{ $stats['total_professionals'] ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active</div>
                    <div class="stat-value" x-text="stats.active_professionals || 0">{{ $stats['active_professionals'] ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value" x-text="stats.total_patients || 0">{{ $stats['total_patients'] ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Suspended</div>
                    <div class="stat-value" x-text="stats.suspended_professionals || 0">{{ $stats['suspended_professionals'] ?? 0 }}</div>
                </div>
            </div>

            <div class="professionals-panel">
                <!-- Toolbar -->
                <div class="toolbar">
                    <input 
                        type="text" 
                        class="search-box" 
                        placeholder="Search by name, email, or clinic..."
                        x-model="searchTerm"
                        @input="filterProfessionals()"
                    >
                    <div class="filter-tabs">
                        <template x-for="status in ['All', 'Active', 'Inactive', 'Suspended', 'Pending']" :key="status">
                            <button 
                                class="filter-btn" 
                                :class="{ active: filterStatus === status }"
                                @click="filterStatus = status; filterProfessionals()"
                                x-text="status"
                            ></button>
                        </template>
                    </div>
                    <select class="filter-btn" x-model.number="pagination.per_page" @change="changePerPage()">
                        <option :value="5">5 / page</option>
                        <option :value="10">10 / page</option>
                    </select>
                    <button class="add-button" @click="showAddModal = true">+ Add Professionals</button>
                </div>

                <!-- Add Modal -->
                <div class="modal-overlay" :class="{ active: showAddModal }">
                    <div class="modal">
                        <div class="modal-header">
                            <h3>Add New Professional</h3>
                        </div>
                        <div class="modal-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input 
                                        type="text" 
                                        placeholder="First Name"
                                        x-model="newProfessional.first_name"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input 
                                        type="text" 
                                        placeholder="Last Name"
                                        x-model="newProfessional.last_name"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input 
                                        type="email"
                                        placeholder="Email"
                                        x-model="newProfessional.email"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input 
                                        type="text"
                                        placeholder="Phone"
                                        x-model="newProfessional.phone"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Specialty</label>
                                    <input 
                                        type="text"
                                        placeholder="Specialty"
                                        x-model="newProfessional.specialty"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Clinic</label>
                                    <input 
                                        type="text"
                                        placeholder="Clinic"
                                        x-model="newProfessional.clinic"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>License</label>
                                    <input 
                                        type="text"
                                        placeholder="License Number"
                                        x-model="newProfessional.license_number"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Location</label>
                                    <input 
                                        type="text"
                                        placeholder="Location"
                                        x-model="newProfessional.location"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-primary" @click="addProfessional()">Add</button>
                            <button class="btn-secondary" @click="showAddModal = false">Cancel</button>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal -->
                <div class="modal-overlay" :class="{ active: editingProfessional !== null }">
                    <div class="modal" x-show="editingProfessional !== null">
                        <div class="modal-header">
                            <h3>Edit Professional</h3>
                        </div>
                        <div class="modal-body" x-show="editingProfessional !== null">
                            <div class="form-grid edit">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input 
                                        type="text"
                                        x-model="editingProfessional.first_name"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input 
                                        type="text"
                                        x-model="editingProfessional.last_name"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input 
                                        type="email"
                                        x-model="editingProfessional.email"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select x-model="editingProfessional.status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" x-model="editingProfessional.phone">
                                </div>
                                <div class="form-group">
                                    <label>Clinic</label>
                                    <input type="text" x-model="editingProfessional.clinic">
                                </div>
                                <div class="form-group">
                                    <label>Location</label>
                                    <input type="text" x-model="editingProfessional.location">
                                </div>
                                <div class="form-group">
                                    <label>Specialty</label>
                                    <input type="text" x-model="editingProfessional.specialty">
                                </div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label>License Number</label>
                                    <input type="text" x-model="editingProfessional.license_number">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-save" @click="saveEdit()">Save</button>
                            <button class="btn-secondary" @click="editingProfessional = null">Cancel</button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Professionals</th>
                                <th>Contact</th>
                                <th>Clinic</th>
                                <th>Patients</th>
                                <th>Verified</th>
                                <th>Status</th>
                                <th>Last Active</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($professionals as $pro)
                                <tr class="server-row">
                                    <td>
                                        <div class="professional-name">{{ $pro['name'] ?? 'Unnamed User' }}</div>
                                        <div class="professional-specialty">{{ $pro['specialty'] ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div class="professional-email">{{ $pro['email'] ?? 'N/A' }}</div>
                                        <div class="professional-phone">{{ $pro['phone'] ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div class="clinic-info">
                                            <div class="clinic-name">{{ $pro['clinic'] ?? 'N/A' }}</div>
                                            <div class="clinic-license">License: {{ $pro['license_number'] ?? 'N/A' }}</div>
                                        </div>
                                    </td>
                                    <td><span class="patient-badge">{{ $pro['patients'] ?? 0 }}</span></td>
                                    <td>{{ !empty($pro['is_verified']) ? 'Verified' : 'Pending' }}</td>
                                    <td>
                                        <span class="status-badge status-{{ strtolower($pro['status'] ?? 'active') }}">{{ ucfirst(strtolower($pro['status'] ?? 'active')) }}</span>
                                    </td>
                                    <td>
                                        <div class="last-active-time">{{ $pro['last_active'] ?? 'Never' }}</div>
                                        <div class="joined-date">Joined: {{ $pro['joined_date'] ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div class="action-buttons"><span class="action-btn edit">Edit</span></div>
                                    </td>
                                </tr>
                            @endforeach

                            @if(count($professionals) === 0)
                                <tr class="server-row">
                                    <td colspan="8" style="text-align:center; color:#6b7280; padding:24px;">No professionals found. Verified doctor accounts will appear here.</td>
                                </tr>
                            @endif

                            <template x-for="pro in filteredProfessionals" :key="pro.id">
                                <tr>
                                    <td>
                                        <div class="professional-name" x-text="pro.name"></div>
                                        <div class="professional-specialty" x-text="pro.specialty"></div>
                                    </td>
                                    <td>
                                        <div class="professional-email" x-text="pro.email"></div>
                                        <div class="professional-phone" x-text="pro.phone"></div>
                                    </td>
                                    <td>
                                        <div class="clinic-info">
                                            <div class="clinic-name" x-text="pro.clinic"></div>
                                            <div class="clinic-license" x-text="'License: ' + pro.license_number"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="patient-badge" x-text="pro.patients"></span>
                                    </td>
                                    <td>
                                        <label class="verification-slider">
                                            <input type="checkbox" :checked="pro.is_verified" @change="toggleVerification(pro)">
                                            <span class="v-slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <span 
                                            class="status-badge"
                                            :class="'status-' + normalizeStatus(pro.status)"
                                            x-text="capitalizeStatus(normalizeStatus(pro.status))"
                                        ></span>
                                    </td>
                                    <td>
                                        <div class="last-active-time" x-text="pro.last_active"></div>
                                        <div class="joined-date" x-text="'Joined: ' + pro.joined_date"></div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button 
                                                class="action-btn edit"
                                                @click="editProfessional(pro)"
                                            >Edit</button>
                                            <button 
                                                class="action-btn"
                                                :class="normalizeStatus(pro.status) === 'active' ? 'delete' : 'edit'"
                                                @click="toggleStatus(pro, 'doctor')"
                                                x-text="normalizeStatus(pro.status) === 'active' ? 'Disable' : 'Enable'"></button>
                                            <button 
                                                class="action-btn delete"
                                                @click="deleteProfessional(pro.id)"
                                            >Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="filteredProfessionals.length === 0">
                                <tr>
                                    <td colspan="8" style="text-align:center; color:#6b7280; padding:24px;">No professionals found. Verified doctor accounts will appear here.</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="toolbar" style="justify-content: space-between; margin-top: 16px;">
                    <div style="color: #6b7280; font-size: 14px;" x-text="paginationText()"></div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button class="action-btn edit" :disabled="pagination.current_page <= 1" @click="loadProfessionals(pagination.current_page - 1)">Prev</button>
                        <span style="font-size: 14px; color: #374151;" x-text="`Page ${pagination.current_page} of ${Math.max(pagination.last_page, 1)}`"></span>
                        <button class="action-btn edit" :disabled="pagination.current_page >= pagination.last_page" @click="loadProfessionals(pagination.current_page + 1)">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div id="settings" class="content-section">
            @if(session('success'))
                <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="settings-form" action="{{ url('/admin/settings') }}" method="POST">
                @csrf
                <div class="form-section">
                    <div>
                        <h3>Account Settings</h3>
                        <p class="section-subtitle">Manage your account information</p>
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="{{ $admin->first_name ?? '' }}" placeholder="Enter first name" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="{{ $admin->last_name ?? '' }}" placeholder="Enter last name" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="{{ $admin->email }}" placeholder="Enter email" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" value="{{ $admin->phone ?? '' }}" placeholder="Enter phone number">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div>
                        <h3>Change Password</h3>
                        <p class="section-subtitle">Update your password regularly</p>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" placeholder="Enter current password">
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" placeholder="Enter new password">
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="new_password_confirmation" placeholder="Confirm password">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div>
                        <h3>Administrator Access</h3>
                        <p class="section-subtitle">SIGHT enforces a single super-admin account. Secondary admin creation has been disabled.</p>
                        <p style="margin-top: 12px; color:#6b7280;">Signed in as {{ $admin->display_name }} ({{ $admin->email }}).</p>
                    </div>
                </div>

                <button type="submit" class="save-button">Save All Settings</button>
            </form>
        </div>

        <!-- Audit Logs Section -->
        <div id="audit-logs" class="content-section">
            <div class="professionals-panel">
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 18px; font-weight: 700; color: #1f2937;">System Audit Logs</h3>
                    <p style="color: #6b7280; font-size: 13px;">Immutable record of administrative actions.</p>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Administrator</th>
                                <th>Action Taken</th>
                                <th>Target Entity</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($auditLogs) && count($auditLogs) > 0)
                                @foreach($auditLogs as $log)
                                    <tr>
                                        <td style="color: #6b7280;">#{{ $log['id'] }}</td>
                                        <td style="font-weight: 600;">{{ $log['admin_name'] }}</td>
                                        <td><span class="status-badge" style="background: #E5FFF6; color: #527267;">{{ $log['action'] }}</span></td>
                                        <td style="font-family: monospace; font-size: 12px; color: #4b5563;">{{ $log['target'] }}</td>
                                        <td style="color: #9ca3af; font-size: 12px;">{{ $log['ip'] }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" style="text-align:center; color:#6b7280; padding:24px;">No audit logs recorded yet.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

<script>
        document.addEventListener('alpine:init', () => {
            document.querySelectorAll('.server-row').forEach((row) => {
                row.style.display = 'none';
            });
        });

        function moveIndicator(element) {
            const indicator = document.getElementById('tab-indicator');
            if (!indicator || !element) return;
            
            indicator.style.width = element.offsetWidth + 'px';
            indicator.style.left = element.offsetLeft + 'px';
            indicator.style.height = element.offsetHeight + 'px';
            indicator.style.top = element.offsetTop + 'px';
        }

        function showSection(element, sectionId) {
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            element.classList.add('active');
            
            moveIndicator(element);
        }

        window.addEventListener('load', () => moveIndicator(document.querySelector('.tab.active')));
        window.addEventListener('resize', () => moveIndicator(document.querySelector('.tab.active')));

        function professionalsManager() {
            return {
                professionals: @json($professionals),
                otherAdmins: [],
                stats: @json($stats),
                pagination: @json($pagination),
                csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                filteredProfessionals: @json($professionals),
                searchTerm: '',
                filterStatus: 'All',
                showAddModal: false,
                showAddAdminModal: false,
                editingProfessional: null,
                isSubmittingAdmin: false,
                newAdmin: {
                    first_name: '',
                    last_name: '',
                    email: '',
                    password: '',
                    password_confirmation: ''
                },
                newProfessional: {
                    first_name: '',
                    last_name: '',
                    email: '',
                    phone: '',
                    specialty: '',
                    clinic: '',
                    license_number: '',
                    location: '',
                    password: '',
                    password_confirmation: ''
                },
                toasts: [],

                notify(type, message) {
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, type, message });
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 3200);
                },

                async loadProfessionals(page = 1) {
                    const params = new URLSearchParams({
                        page: String(page),
                        per_page: String(this.pagination.per_page || 10),
                        status: this.filterStatus,
                        search: this.searchTerm || ''
                    });

                    try {
                        const response = await fetch(`/admin/professionals?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Failed to load professionals');
                        }

                        this.professionals = data.professionals || [];
                        this.filteredProfessionals = [...this.professionals];
                        this.pagination = {
                            ...this.pagination,
                            ...(data.pagination || {})
                        };
                    } catch (error) {
                        this.notify('error', error.message || 'Failed to load professionals.');
                    }
                },

                filterProfessionals() {
                    this.loadProfessionals(1);
                },

                changePerPage() {
                    if (![5, 10].includes(Number(this.pagination.per_page))) {
                        this.pagination.per_page = 10;
                    }
                    this.loadProfessionals(1);
                },

                paginationText() {
                    const total = Number(this.pagination.total || 0);
                    if (!total) {
                        return 'No professionals found';
                    }
                    const from = this.pagination.from || 0;
                    const to = this.pagination.to || 0;
                    return `Showing ${from}-${to} of ${total}`;
                },

                capitalizeStatus(status) {
                    if (!status) return 'Active';
                    return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
                },

                normalizeStatus(status) {
                    return (status || 'active').toString().toLowerCase();
                },

                splitName(name) {
                    const parts = (name || '').trim().split(/\s+/).filter(Boolean);
                    if (!parts.length) {
                        return { first_name: '', last_name: '' };
                    }
                    if (parts.length === 1) {
                        return { first_name: parts[0], last_name: '' };
                    }
                    return {
                        first_name: parts.slice(0, -1).join(' '),
                        last_name: parts[parts.length - 1]
                    };
                },

                async addProfessional() {
                    if (!this.newProfessional.first_name || !this.newProfessional.last_name || !this.newProfessional.email || !this.newProfessional.clinic) {
                        this.notify('error', 'Please fill in all required fields.');
                        return;
                    }

                    const payload = {
                        first_name: this.newProfessional.first_name,
                        last_name: this.newProfessional.last_name,
                        email: this.newProfessional.email,
                        phone: this.newProfessional.phone,
                        specialty: this.newProfessional.specialty,
                        clinic: this.newProfessional.clinic,
                        license_number: this.newProfessional.license_number,
                        location: this.newProfessional.location
                    };

                    try {
                        const response = await fetch('/admin/professionals', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            await this.loadProfessionals(1);
                            
                            this.showAddModal = false;
                            this.resetNewProfessional();

                            this.notify('success', `Professional added. Temp password: ${data.temp_password}`);
                        } else {
                            // Display specific validation or server error messages
                            let errorMessage = data.message || 'Error adding professional';
                            if (data.errors) {
                                errorMessage = Object.values(data.errors).flat().join('\n');
                            }
                            this.notify('error', errorMessage);
                        }
                    } catch (error) {
                        console.error('Submission Error:', error);
                        this.notify('error', 'Failed to connect to the server. Please try again.');
                    }
                },

                async submitAddAdmin() {
                    this.notify('error', 'Secondary admin creation is disabled.');
                },

                async removeAdmin(id) {
                    this.notify('error', 'Secondary admin removal is disabled.');
                },

                resetNewProfessional() {
                    this.newProfessional = { 
                        first_name: '', last_name: '', email: '', phone: '', specialty: '', 
                        clinic: '', license_number: '', location: '',
                        password: '', password_confirmation: ''
                    };
                },

                editProfessional(pro) {
                    const parsed = this.splitName(pro.name || '');
                    this.editingProfessional = {
                        ...pro,
                        first_name: pro.first_name || parsed.first_name,
                        last_name: pro.last_name || parsed.last_name,
                        status: this.normalizeStatus(pro.status)
                    };
                },

                async saveEdit() {
                    if (this.editingProfessional) {
                        try {
                            const payload = {
                                first_name: this.editingProfessional.first_name,
                                last_name: this.editingProfessional.last_name,
                                email: this.editingProfessional.email,
                                phone: this.editingProfessional.phone,
                                clinic: this.editingProfessional.clinic,
                                specialty: this.editingProfessional.specialty,
                                license_number: this.editingProfessional.license_number,
                                location: this.editingProfessional.location,
                                status: this.normalizeStatus(this.editingProfessional.status)
                            };

                            const response = await fetch(`/admin/professionals/${this.editingProfessional.id}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken
                                },
                                body: JSON.stringify(payload)
                            });

                            const data = await response.json();
                            if (!response.ok || !data.success) {
                                let errorMessage = data.message || 'Update failed';
                                if (data.errors) {
                                    errorMessage = Object.values(data.errors).flat().join('\n');
                                }
                                throw new Error(errorMessage);
                            }

                            const updated = data.professional || {
                                ...this.editingProfessional,
                                name: `${payload.first_name} ${payload.last_name}`.trim(),
                                status: payload.status
                            };

                            const index = this.professionals.findIndex(p => p.id === this.editingProfessional.id);
                            if (index !== -1) {
                                this.professionals[index] = updated;
                            }

                            this.filteredProfessionals = [...this.professionals];
                            this.editingProfessional = null;
                            this.notify('success', data.message || 'Professional updated successfully.');
                        } catch (error) {
                            this.notify('error', error.message || 'Failed to save changes to the server.');
                        }
                    }
                },

                async deleteProfessional(id) {
                    if (confirm('Are you sure you want to delete this professional?')) {
                        try {
                            const response = await fetch(`/admin/professionals/${id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': this.csrfToken }
                            });
                            
                            if (!response.ok) throw new Error('Delete failed');
                            await this.loadProfessionals(this.pagination.current_page);
                            this.notify('success', 'Professional deleted successfully.');
                        } catch (error) {
                            this.notify('error', 'Failed to delete from server.');
                        }
                    }
                },

                async toggleStatus(user, type) {
                    const action = this.normalizeStatus(user.status) === 'active' ? 'disable' : 'enable';
                    if (!confirm(`Are you sure you want to ${action} this account?`)) return;

                    try {
                        const response = await fetch(`/admin/users/${user.id}/toggle-status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            const updatedStatus = this.normalizeStatus(data.status);
                            user.status = updatedStatus;
                            if (type === 'doctor') {
                                this.professionals = this.professionals.map(p =>
                                    p.id === user.id ? { ...p, status: updatedStatus } : p
                                );
                                this.filteredProfessionals = [...this.professionals];
                            } else if (type === 'admin') {
                                this.otherAdmins = this.otherAdmins.map(a =>
                                    a.id === user.id ? { ...a, status: updatedStatus } : a
                                );
                            }
                            this.notify('success', data.message || 'Status updated successfully.');
                        } else {
                            this.notify('error', data.message || 'Failed to update status.');
                        }
                    } catch (error) {
                        console.error('Toggle Error:', error);
                        this.notify('error', 'Failed to update status.');
                    }
                },

                async toggleVerification(pro) {
                    try {
                        const response = await fetch(`/admin/professionals/${pro.id}/toggle-verification`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            pro.is_verified = data.is_verified;
                            this.notify('success', data.message || 'Verification updated successfully.');
                        } else {
                            this.notify('error', data.message || 'Failed to update verification.');
                            // Revert checkbox state if failed
                            pro.is_verified = !pro.is_verified;
                        }
                    } catch (error) {
                        console.error('Verification Error:', error);
                        this.notify('error', 'A connection error occurred. Please try again.');
                    }
                },

                init() {
                    this.filteredProfessionals = [...this.professionals];
                }
            };
        }
    </script>
</body>
</html>
