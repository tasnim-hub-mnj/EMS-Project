<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BoothBookingController;
use App\Http\Controllers\BoothController;
use App\Http\Controllers\BoothManagementController;
use App\Http\Controllers\BoothReviewController;
use App\Http\Controllers\CollectedBoothController;
use App\Http\Controllers\DashboardInvestorController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExhibitionController;
use App\Http\Controllers\ExhibitionReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\ProfileVisitorController;
use App\Http\Controllers\InvestorReportsController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\PortalInviteController;
use App\Http\Controllers\PortalLinkController;
use App\Http\Controllers\SponsorshipBookingController;
use App\Http\Controllers\SponsorEventController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\verifyOtpController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\VisitorScheduleController;
use App\Http\Controllers\CopyReportController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\InvestorsSummaryController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\SponsorshipRequestController;
use App\Http\Controllers\EventSponsorshipRequestController;
use App\Http\Controllers\ExternalTeamController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\StaffMemberController;
use App\Http\Controllers\StaffTaskController;
use App\Http\Controllers\FirebaseSyncController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FcmTokenController;
use Illuminate\Support\Facades\Route;

//================================================================
//================================================================
//FCM Token
Route::post('/notifications/fcm-token', [FcmTokenController::class, 'store']);

//Notifications
Route::get('/notifications', [NotificationController::class, 'index']);
Route::get('/notifications/unread', [NotificationController::class, 'unread']);
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

//Firebase Sync
Route::post('/auth/firebase-sync', [FirebaseSyncController::class, 'syncO']);
Route::post('/auth/firebase-sync', [FirebaseSyncController::class, 'syncI']);

//================================================================
//***********************Organizer*********************************
//================================================================
//Auth
Route::post('/organizer/auth/register', [OrganizerController::class, 'register']);
Route::post('/organizer/auth/verify-otp', [verifyOtpController::class, 'verifyOtp']);
Route::post('/organizer/auth/resend-otp', [verifyOtpController::class, 'resendOtp']);
Route::post('/organizer/auth/login', [OrganizerController::class, 'login'])->middleware('throttle:log');

Route::post('/organizer/auth/forgot-password', [verifyOtpController::class, 'forgotPassword1']);
Route::post('/organizer/auth/reset-password', [verifyOtpController::class, 'resetPassword2']);

// Route::get('/staff/claim/{link}', [StaffMemberController::class, 'claimRole']);

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('checkOrganizer')->group(function () {
        //Auth
        Route::post('/organizer/auth/change-password', [verifyOtpController::class, 'updatePassword']);
        // Route::post('/organizer/auth/delete-account', [verifyOtpController::class, 'deleteAccount']);
        Route::post('/organizer/auth/logout', [verifyOtpController::class, 'logout']);

        Route::put('/organizer/auth/profile', [OrganizerController::class, 'updateProfile']);
        Route::put('/organizer/auth/company', [OrganizerController::class, 'updateCompany']);
        Route::get('/organizer/auth/me', [OrganizerController::class, 'getPorfile']);

        //Exhibitions
        Route::post('/organizer/exhibitions', [ExhibitionController::class, 'store']);//✔️working_hours
        Route::put('/organizer/exhibitions/{id}', [ExhibitionController::class, 'update']);//✔️working_hours
        Route::get('/organizer/exhibitions', [ExhibitionController::class, 'index']);
        Route::get('/organizer/exhibitions/organizer', [ExhibitionController::class, 'organizerExhibition']);
        Route::get('/organizer/exhibitions/{id}', [ExhibitionController::class, 'showExhibition']);
        Route::patch('/organizer/exhibitions/{id}/map-built', [ExhibitionController::class, 'BuiltMap']);
        Route::post('/organizer/exhibitions/{id}/archive', [ExhibitionController::class, 'archive']);
        Route::delete('/organizer/exhibitions/{id}', [ExhibitionController::class, 'destroy']);

        //Reports and analytics
        Route::get('/organizer/reports/visitor-stats/{exhibitionId}', [CopyReportController::class, 'visitorStats']);
        Route::get('/organizer/reports/booking-stats/{exhibitionId}', [CopyReportController::class, 'bookingStats']);
        Route::get('/organizer/reports/staff-stats/{exhibitionId}', [CopyReportController::class, 'staffStats']);
        Route::get('/organizer/reports/revenue-timeline/{exhibitionId}', [CopyReportController::class, 'revenueTimeline']);
        Route::get('/organizer/reports/edition-comparisons/{exhibitionId}', [CopyReportController::class, 'editionComparisons']);
        Route::get('/organizer/reports/{reportType}/export.pdf', [CopyReportController::class, 'exportPdf']);

        //Map builder
        Route::post('/organizer/exhibitions/{id}/map', [MapController::class, 'store']);
        Route::post('/organizer/exhibitions/{id}/map/{mapId}', [MapController::class, 'update']);
        Route::get('/organizer/exhibitions/{id}/map', [MapController::class, 'show']);
        Route::post('/organizer/exhibitions/{id}/map/json', [MapController::class, 'saveRaw']);
        Route::get('/organizer/exhibitions/{id}/map/history', [MapController::class, 'history']);
        Route::patch('/organizer/exhibitions/{id}/map/{mapId}/publish', [MapController::class, 'publish']);

        //Booths
        Route::post('/organizer/exhibitions/{exhibitionId}/booths', [BoothController::class, 'store']);
        Route::put('/organizer/booths/{id}', [BoothController::class, 'update']);
        Route::get('/organizer/exhibitions/{exhibitionId}/booths', [BoothController::class, 'index']);
        Route::get('/organizer/booths/{id}', [BoothController::class, 'show']);
        Route::post('/organizer/booths/{id}', [BoothController::class, 'updateWithImage']);
        Route::patch('/organizer/booths/{id}/status', [BoothController::class, 'changeStatus']);
        Route::delete('/organizer/booths/{id}', [BoothController::class, 'destroy']);

        //Bookings
        Route::post('/organizer/bookings', [BoothBookingController::class, 'store']);
        Route::get('/organizer/bookings', [BoothBookingController::class, 'index']);
        Route::get('/organizer/exhibitions/{exhibitionId}/past-edition-bookings', [BoothBookingController::class, 'pastEditionBookings']);
        Route::get('/organizer/bookings/{id}', [BoothBookingController::class, 'show']);
        Route::post('/organizer/bookings/{id}/approve', [BoothBookingController::class, 'approve']);
        Route::post('/organizer/bookings/{id}/reject', [BoothBookingController::class, 'reject']);
        Route::get('/organizer/bookings/{id}/contract.pdf', [BoothBookingController::class, 'contractPdf']);

        //Sponsor Events and Invitations
        Route::post('/organizer/events', [SponsorEventController::class, 'store']);
        Route::put('/organizer/events/{id}', [SponsorEventController::class, 'update']);
        Route::get('/organizer/events', [SponsorEventController::class, 'index']);
        Route::get('/organizer/events/{id}', [SponsorEventController::class, 'show']);
        Route::post('/organizer/events/{id}/publish', [SponsorEventController::class, 'publish']);
        Route::delete('/organizer/events/{id}', [SponsorEventController::class, 'destroy']);
        Route::get('/organizer/events/{eventId}/analytics', [SponsorEventController::class, 'analytics']);

        Route::get('/organizer/events/{eventId}/tickets', [SponsorEventController::class, 'getAllInvitation']);
        Route::post('/organizer/events/{eventId}/tickets', [SponsorEventController::class, 'storeInvitation']);
        Route::put('/organizer/tickets/{id}', [SponsorEventController::class, 'updateInvitation']);
        Route::post('/organizer/tickets/{ticketId}/attend', [SponsorEventController::class, 'attendInvitation']);

        //Investors
        Route::get('/organizer/investors/summary', [InvestorsSummaryController::class, 'summary']);
        Route::get('/organizer/investors/summary/{id}', [InvestorsSummaryController::class, 'summaryDetail']);

        //Sponsors , sponsorship and event sponsorship
        Route::post('/sponsors', [SponsorController::class, 'store']);
        Route::put('/sponsors/{id}', [SponsorController::class, 'update']);
        Route::get('/sponsors', [SponsorController::class, 'index']);
        Route::get('/sponsors/{id}', [SponsorController::class, 'show']);
        Route::delete('/sponsors/{id}', [SponsorController::class, 'delete']);

        Route::get('/sponsorship-requests', [SponsorshipRequestController::class, 'index']);
        Route::put('/sponsorship-requests/{id}', [SponsorshipRequestController::class, 'update']);
        Route::post('/sponsorship-requests/{id}/accept', [SponsorshipRequestController::class, 'accept']);

        Route::get('/event-sponsorship-requests', [EventSponsorshipRequestController::class, 'index']);
        Route::put('/event-sponsorship-requests/{id}', [EventSponsorshipRequestController::class, 'update']);

        //Staff management
        Route::get('/staff', [StaffMemberController::class, 'index']);
        Route::get('/staff/{id}', [StaffMemberController::class, 'show']);
        Route::post('/staff', [StaffMemberController::class, 'store']);
        Route::put('/staff/{id}', [StaffMemberController::class, 'update']);
        Route::delete('/staff/{id}', [StaffMemberController::class, 'destroy']);
        //External Team
        Route::get('/staff/external', [ExternalTeamController::class, 'index']);
        Route::post('/staff/external', [ExternalTeamController::class, 'store']);
        Route::put('/staff/external/{id}', [ExternalTeamController::class, 'update']);
        Route::delete('/staff/external/{id}', [ExternalTeamController::class, 'destroy']);
        //External Team Members
        Route::post('/staff/external/{teamId}/members', [ExternalTeamController::class, 'storeMember']);
        Route::put('/staff/external/{teamId}/members/{memberId}', [ExternalTeamController::class, 'updateMember']);
        Route::delete('/staff/external/{teamId}/members/{memberId}', [ExternalTeamController::class, 'destroyMember']);
        //External Team Tasks
        Route::post('/staff/external/{teamId}/tasks', [ExternalTeamController::class, 'storeTask']);
        Route::put('/staff/external/{teamId}/tasks/{taskId}', [ExternalTeamController::class, 'updateTask']);
        //Staff Task
        Route::get('/staff/tasks', [StaffTaskController::class, 'index']);
        Route::post('/staff/tasks', [StaffTaskController::class, 'store']);
        Route::put('/staff/tasks/{id}', [StaffTaskController::class, 'update']);
        Route::delete('/staff/tasks/{id}', [StaffTaskController::class, 'destroy']);
        //Staff Attendance
        Route::get('/staff/attendance', [AttendanceController::class, 'index']);
        Route::post('/staff/attendance', [AttendanceController::class, 'store']);
        //Staff Payroll
        Route::get('/staff/payroll', [PayrollController::class, 'summary']);
        Route::get('/staff/payroll/entries', [PayrollController::class, 'entries']);

        //Staff portal links and employee access control
        Route::get('/staff/portal-links', [PortalLinkController::class, 'index']);
        Route::post('/staff/portal-links', [PortalLinkController::class, 'store']);
        Route::get('/staff/portal-links/{token}', [PortalLinkController::class, 'show']);
        Route::patch('/staff/portal-links/{token}/deactivate', [PortalLinkController::class, 'deactivate']);
        Route::delete('/staff/portal-links/{token}', [PortalLinkController::class, 'destroy']);

        Route::post('/staff/portal-invite', [PortalInviteController::class, 'send']);
    });


    //***********Admin Dashboard & Users Management*************
    Route::middleware('checkAdmin')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::get('users/approved', [AdminController::class, 'approvedUsers']);
        Route::get('users/pending', [AdminController::class, 'pendingUsers']);

        //****Change User Approve Status*****
        Route::put('users/{id}/approve', [AdminController::class, 'approveUser']);
        Route::put('users/{id}/rejecte', [AdminController::class, 'rejecteUser']);
        Route::delete('users/{id}', [AdminController::class, 'deleteUser']);
    });
});

//================================================================
//***********************Investor*********************************
//================================================================
//Auth
Route::post('/investor/auth/register', [InvestorController::class, 'register']);
Route::post('/investor/auth/verify-otp', [verifyOtpController::class, 'verifyOtp']);
Route::post('/investor/auth/resend-otp', [verifyOtpController::class, 'resendOtp']);
Route::post('/investor/auth/login', [InvestorController::class, 'login'])->middleware('throttle:log');

Route::post('/investor/auth/forgot-password', [verifyOtpController::class, 'forgotPassword1']);// الخطوة 1: إرسال OTP
Route::post('/investor/auth/forgot-password/verify-otp', [verifyOtpController::class, 'forgotPassword2']);// الخطوة 2: التحقق من OTP
Route::post('/investor/auth/reset-password', [verifyOtpController::class, 'resetPassword']);// الخطوة 3: تعيين كلمة مرور جديدة

//Reports--download
Route::get('/investor/reports/{id}/download', [InvestorReportsController::class, 'downloadReport']);

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('checkInvestor')->group(function () {
        Route::post('/investor/auth/change-password', [verifyOtpController::class, 'updatePassword']);
        Route::post('/investor/auth/fcm-token', [verifyOtpController::class, 'saveFcmToken']);
        Route::post('/investor/auth/delete-account', [verifyOtpController::class, 'deleteAccount']);
        Route::post('/investor/auth/logout', [verifyOtpController::class, 'logout']);

        //Dashboard
        Route::get('/investor/exhibitions/featured', [DashboardInvestorController::class, 'featuredExhibitions']);
        Route::get('/investor/sponsor-events/featured', [DashboardInvestorController::class, 'featuredSponsorEvents']);
        Route::get('/investor/dashboard', [DashboardInvestorController::class, 'dashboard']);
        Route::get('/investor/exhibitions/latest', [DashboardInvestorController::class, 'latestExhibitions']);

        //Exhibitions
        Route::get('/investor/exhibitions', [ExhibitionController::class, 'getAllExhibitions']);
        Route::get('/investor/exhibitions/{id}', [ExhibitionController::class, 'show']);

        //Booths
        Route::get('/investor/booths', [BoothController::class, 'getAvailableBooths']);
        Route::get('/investor/exhibition/booths', [BoothController::class, 'getExhibitionBooths']);
        Route::get('/investor/booths/{id}', [BoothController::class, 'getBoothDetail']);

        //Booking
        Route::post('/booths/book', [BoothBookingController::class, 'bookBooth']);
        Route::patch('/investor/bookings/{id}/cancel', [BoothBookingController::class, 'cancelBooking']);
        Route::get('/investor/bookings/{id}', [BoothBookingController::class, 'getBookingDetail']);
        Route::get('/investor/bookings', [BoothBookingController::class, 'myBookings']);

        //Booth Profile
        Route::get('/investor/booths/{boothId}/profile', [BoothManagementController::class, 'getBoothProfile']);
        Route::post('/investor/booths/{boothId}/profile/update', [BoothManagementController::class, 'updateBoothProfile']);
        Route::post('/investor/booths/{boothId}/cover', [BoothManagementController::class, 'uploadBoothCover']);
        Route::get('/investor/booth/events', [BoothManagementController::class, 'getBoothEvents']);

        //Event
        Route::post('/investor/events', [EventController::class, 'createEvent']);//❌
        Route::get('/investor/events', [EventController::class, 'getInvestorEvents']);
        Route::get('/investor/events/{id}/ticket-requests', [EventController::class, 'getTicketRequests']);
        Route::patch('/investor/events/{eventId}/ticket-requests/{requestId}', [EventController::class, 'ticketRequestAction']);
        //SponsorEvent
        Route::get('/investor/sponsor-events', [SponsorEventController::class, 'getSponsorEvents']);
        Route::post('/investor/sponsorships', [SponsorshipBookingController::class, 'createSponsorship']);//❌
        Route::patch('/investor/sponsorships/{id}/cancel', [SponsorshipBookingController::class, 'cancelSponsorship']);
        Route::get('/investor/sponsorships', [SponsorshipBookingController::class, 'getMySponsorships']);

        //Reports
        Route::get('/investor/reports', [InvestorReportsController::class, 'getReports']);
        Route::get('/investor/reports/{id}', [InvestorReportsController::class, 'getReportDetail']);
        // Route::get('/investor/reports/{id}/download', [InvestorReportsController::class, 'downloadReport']);

        //Favorites
        Route::get('/investor/favorites', [FavoriteController::class, 'getFavoritesInvestor']);
        Route::post('/investor/favorites/{id}', [FavoriteController::class, 'addFavorite']);
        Route::delete('/investor/favorites/{id}', [FavoriteController::class, 'removeFavorite']);

        //Profile
        Route::get('/investor/profile', [InvestorController::class, 'getProfile']);
        Route::put('/investor/profile', [InvestorController::class, 'updateProfile']);
        Route::post('/investor/profile/avatar', [InvestorController::class, 'uploadAvatar']);
    });
});

//___________________________________________________________________________________
//___________________________________________________________________________________
//*****************************************************************************
//**********************************HANAN😁Visitor****************************
//****************************************************************************

Route::post('/auth/login', [VisitorController::class, 'login'])->middleware('throttle:log');
Route::post('/auth/register', [VisitorController::class, 'register']);
Route::post('/visitor/auth/verify-otp', [verifyOtpController::class, 'verifyOtp']);
Route::post('/visitor/auth/resend-otp', [verifyOtpController::class, 'resendOtp']);
Route::post('/visitor/auth/forgot-password', [verifyOtpController::class, 'forgotPassword1']);// الخطوة 1: إرسال OTP
Route::post('/visitor/auth/forgot-password/verify-otp', [verifyOtpController::class, 'forgotPassword2']);// الخطوة 2: التحقق من OTP
Route::post('/visitor/auth/reset-password', [verifyOtpController::class, 'resetPassword']);// الخطوة 3: تعيين كلمة مرور جديدة



Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('checkIsVisitor')->group(function () {
        Route::post('/visitor/auth/change-password', [verifyOtpController::class, 'updatePassword']);
        Route::post('/visitor/auth/fcm-token', [verifyOtpController::class, 'saveFcmToken']);

        //profile+logout
        Route::get('/profile', [ProfileVisitorController::class, 'getProfile']);
        Route::match(['put', 'post'], '/profile/update', [ProfileVisitorController::class, 'updateProfile']);
        Route::post('/profile/delete-account', [ProfileVisitorController::class, 'deleteAccount']);
        Route::post('/profile/change-password', [ProfileVisitorController::class, 'changePassword']);
        Route::post('/auth/logout', [VisitorController::class, 'logout']);


        //*************/

        //exh
        Route::get('/visitor/exhibitions', [ExhibitionController::class, 'getAllExhibitions']);
        Route::get('/visitor/exhibitions/{id}', [ExhibitionController::class, 'show']);
        Route::get('/exhibitions', [ExhibitionController::class, 'featuredExhibitionsForVisitor']);
        Route::get('/exhibitions/{id}/events', [ExhibitionController::class, 'getEventsExh']);
        Route::get('/exhibitions/{id}/booths', [ExhibitionController::class, 'getBoothsExh']);
        Route::get('/exhibitions/{id}/map', [ExhibitionController::class, 'getFloorMap']);

        //exh review
        Route::get('/exhibitions/{id}/reviews', [ExhibitionReviewController::class, 'getExhibitionReviews']);
        Route::get('/reviews/exhibitions/all', [ExhibitionReviewController::class, 'getAllExhibitionsReviews']);
        Route::post('/reviews/exhibition', [ExhibitionReviewController::class, 'submitExhibitionReview']);

        //__________________________________________________________________________________________
        //booth review
        Route::get('/visitor/booths', [BoothController::class, 'getAvailableBooths']);
        Route::get('/visitor/booths/{id}', [BoothController::class, 'getBoothDetail']);
        Route::get('/booths/{id}/reviews', [BoothReviewController::class, 'getBoothReviews']);
        Route::get('/reviews/booths/all', [BoothReviewController::class, 'getAllBoothsReviews']);
        Route::post('/reviews/booth', [BoothReviewController::class, 'submitBoothReview']);

        //____________________________________________________________________________________________
// عرض كل تقييمات زائر معيّن (معرض + جناح)
        Route::get('/visitor/{id}/reviews', [VisitorController::class, 'visitorReviews']);

        //____________________________________________________________________________________________

        //support ticket
        Route::get('/support/tickets', [SupportTicketController::class, 'AllTickets']);
        Route::get('/support/tickets/{id}', [SupportTicketController::class, 'show']);
        Route::post('/support/messages', [SupportTicketController::class, 'storeMessage']);
        Route::post('/support/report', [SupportTicketController::class, 'storeReport']);
        Route::post('/support/location', [SupportTicketController::class, 'sendLocation']);
        //__________________________________________________________________________________________



        //تذاكر الزائر
        Route::get('/bookings/my-tickets', [TicketController::class, 'myTickets']);
        Route::post('/bookings/exhibition', [TicketController::class, 'bookExhibition']);
        Route::get('/booking/exhibition/{id}', [TicketController::class, 'getExhibitionTicket']);
        Route::post('/bookings/event', [TicketController::class, 'bookEvent']);
        Route::get('/booking/event/{id}', [TicketController::class, 'getEventTicket']);
        Route::delete('/bookings/{id}/cancel', [TicketController::class, 'cancelTicket']);
        Route::get('/booking/sponsor-event/{id}', [TicketController::class, 'showSponsorEventTicket']);
        Route::post('/booking/sponsor-event', [TicketController::class, 'bookSponsorEventTicket']);



        //___________________________________________________________________________________________

        Route::get('/schedule', [VisitorScheduleController::class, 'mySchedule']);
        Route::post('/schedule/{eventId}', [VisitorScheduleController::class, 'storeSchedule']);
        Route::delete('/schedule/{eventId}', [VisitorScheduleController::class, 'removeFromSchedule']);

        //_____________________________________________________________________________________________


        Route::get('/collected-booths', [CollectedBoothController::class, 'index']);
        Route::post('/collected-booths', [CollectedBoothController::class, 'store']);
        Route::post('/collected-booths/scan', [CollectedBoothController::class, 'scan']);
        Route::delete('/collected-booths/{id}', [CollectedBoothController::class, 'destroy']);
        //__________________________________________________________________________________________

        Route::get('/events/{id}', [EventController::class, 'getEventById']);
        Route::get('/events', [EventController::class, 'getLatestEvents']);
        //__________________________________________________________________________________________
        Route::get('/visitor/favorites', [FavoriteController::class, 'getFavoritesInvestor']);
        Route::post('/visitor/favorites/{id}', [FavoriteController::class, 'addFavorite']);
        Route::delete('/visitor/favorites/{id}', [FavoriteController::class, 'removeFavorite']);
    });
});









