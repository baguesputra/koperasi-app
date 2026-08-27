# Koperasi App API Implementation Plan

## Overview
This document outlines the implementation of a RESTful API for the Koperasi App to support mobile applications (React Native) and other integrations. The API will mirror the functionality available in the web portal dashboard.

## Goals
1. Provide secure API access using Laravel Sanctum
2. Enable mobile app to perform loan applications, limit increases, and tenor changes
3. Provide dashboard data for mobile consumption
4. Maintain consistency with existing web portal functionality
5. Implement proper authentication, authorization, and data validation

## Authentication
- **Method**: Laravel Sanctum (token-based)
- **Endpoints**:
  - POST `/api/register` (if needed)
  - POST `/api/login` - returns token
  - POST `/api/logout` - revokes token
  - GET `/api/user` - returns current user data
- **Protected Routes**: All API routes under `auth:sanctum` middleware

## API Endpoints

### 1. Authentication
```
POST /api/register
POST /api/login
POST /api/logout
GET /api/user
```

### 2. Pengajuan Anggota (Member Applications)
```
GET    /api/pengajuan-anggota              # List applications (with filters)
GET    /api/pengajuan-anggota/{id}         # Get application details
POST   /api/pengajuan-anggota              # Create new application
PUT/PATCH /api/pengajuan-anggota/{id}     # Update application (if allowed)
DELETE /api/pengajuan-anggota/{id}        # Cancel application (if allowed)
```

### 3. Pinjaman (Loan Applications)
```
GET    /api/pinjaman                       # List loan applications
GET    /api/pinjaman/{id}                  # Get loan details
POST   /api/pinjaman                       # Submit loan application
POST   /api/pinjaman/{id}/cek-nominal      # Check loan eligibility
POST   /api/pinjaman/{id}/simulasi         # Loan simulation
```

### 4. Pengajuan Limit (Limit Increase Applications)
```
GET    /api/pengajuan-limit                # List limit applications
GET    /api/pengajuan-limit/{id}           # Get limit application details
POST   /api/pengajuan-limit                # Submit limit increase request
```

### 5. Percepatan (Tenor Change Applications)
```
GET    /api/percepatan                     # List tenor applications
GET    /api/percepatan/{id}                # Get tenor application details
POST   /api/percepatan                     # Submit tenor change request
POST   /api/percepatan/{id}/preview        # Preview tenor changes
```

### 6. Dashboard Endpoints
```
GET    /api/dashboard/stats                # Key statistics
GET    /api/dashboard/actionable           # Actionable items
GET    /api/dashboard/charts               # Chart data (trends, cash flow)
GET    /api/dashboard/aktivitas            # Recent activity
```

### 7. Master Data (for forms/dropdowns)
```
GET    /api/master-data/cabang
GET    /api/master-data/unit-bisnis
GET    /api/master-data/jabatan
GET    /api/master-data/divisi
GET    /api/master-data/department
GET    /api/master-data/tabel-tenor
GET    /api/master-data/setting-simpanan
```

## Data Structure & Responses

### Standard Response Format
**Success:**
```json
{
  "data": {...},
  "message": "Success"
}
```

**List Endpoints (paginated):**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  }
}
```

**Error Responses:**
```json
{
  "message": "Validation failed",
  "errors": {
    "field": ["Error message"]
  }
}
```

### Sample Pengajuan Anggota Response
```json
{
  "id": 1,
  "nominal": 10000000,
  "tenor_bulan": 12,
  "keperluan": "Pembangunan Rumah",
  "status": "diajukan",
  "tanggal_pengajuan": "01 Jan 2026",
  "disetujui_pada": "02 Jan 2026 10:30",
  "anggota": {
    "id": 123,
    "no_anggota": "ANG-2026-0001",
    "nama": "Budi Santoso",
    "jabatan": "Staff",
    "cabang": "Pusat",
    "unit_bisnis": "Produksi",
    "department": "Operasional",
    "divisi": "Produksi",
    "no_hp": "081234567890"
  },
  "pengaju": {
    "nama": "Budi Santoso"
  }
}
```

## Implementation Phases

### Phase 0: Preparation
- [x] Install Laravel Sanctum
- [x] Publish Sanctum config
- [x] Run migrations
- [x] Configure auth.php
- [ ] Configure CORS (if needed)

### Phase 1: Foundation
- [x] Create routes/api.php
- [x] Create AuthController
- [x] Test authentication flow

### Phase 2: Core Applications
- [x] Create PengajuanAnggotaController
- [ ] Create supporting resources and validation
- [x] Implement index, show, store, update, destroy methods

### Phase 3: Additional Controllers
- [x] Create PinjamanController
- [x] Create PengajuanLimitController
- [x] Create PercepatanController
- [x] Implement all required methods (index, show, store, update, destroy, cek-nominal, simulasi, preview)

### Phase 4: Dashboard & Master Data
- [x] Create DashboardController
- [x] Create MasterDataController
- [x] Implement all dashboard endpoints (stats, charts, aktivitas, actionable)
- [x] Implement master data endpoints (cabang, unit-bisnis, jabatan, divisi, department, tabel-tenor, setting-simpanan)

### Phase 5: Enhancements
- [x] Add filtering, sorting, pagination
- [ ] Implement caching where beneficial
- [x] Add comprehensive validation
- [x] Implement proper authorization checks

### Phase 6: Testing & Documentation
- [x] Test all endpoints with curl/Postman
- [x] Verify data matches web portal
- [x] Test permission boundaries
- [ ] Create Postman collection
- [ ] Document API usage

## Security Considerations
- All write operations require authentication
- Users can only access their own data (unless admin/authorized)
- Input validation on all endpoints
- Idempotency keys for write operations (matching web implementation)
- Rate limiting on auth endpoints
- SQL injection prevention via Eloquent/Query Builder
- XSS prevention via automatic escaping in JSON responses

## Performance Considerations
- Eager loading to prevent N+1 queries
- Select specific columns when possible
- Pagination on all list endpoints
- Caching for expensive dashboard queries
- Proper database indexing (to be reviewed/added)

## Dependencies
- laravel/sanctum (primary)
- fruitcake/laravel-cors (if CORS needed)
- Existing Laravel features: Eloquent, Validation, Resources, Cache

## Tracking Progress
Each checkbox above will be marked as completed during implementation. This document serves as both the plan and tracking mechanism.

## Notes
- API will be developed in plan mode first, then executed
- All implementation will follow existing codebase conventions
- Mobile app will consume these endpoints via React Native
- Future enhancement: Add OpenAPI/Swagger documentation