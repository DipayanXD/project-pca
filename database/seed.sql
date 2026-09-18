-- Campus Resolve: Seed Data

USE `campus_resolve`;

-- Insert Initial Users (Passwords: Student@123 / Admin@123)
-- Hash for Student@123: $2y$10$BL/T7rtupWvJGiqewYckxuzrhfMuM844dcohnQD8NLYVuimecJ3Ky
-- Hash for Admin@123:   $2y$10$/nUMYk2B3DHbQ01fOrZBhuoA7RcnaIzKBU2CPvgcqolUtClJYBIHu

INSERT INTO `users` (`id`, `user_code`, `name`, `email`, `password`, `role`, `department`, `status`, `created_at`) VALUES
(1, 'AD-001', 'Priya Sharma', 'admin@campus.edu', '$2y$10$/nUMYk2B3DHbQ01fOrZBhuoA7RcnaIzKBU2CPvgcqolUtClJYBIHu', 'admin', 'Administration', 'active', '2026-07-28 10:00:00'),
(2, 'ST-2016', 'Alex Johnson', 'alex.johnson@campus.edu', '$2y$10$BL/T7rtupWvJGiqewYckxuzrhfMuM844dcohnQD8NLYVuimecJ3Ky', 'student', 'Computer Application', 'active', '2026-08-12 11:30:00'),
(3, 'ST-2017', 'Maya Patel', 'maya.patel@campus.edu', '$2y$10$BL/T7rtupWvJGiqewYckxuzrhfMuM844dcohnQD8NLYVuimecJ3Ky', 'student', 'Information Technology', 'active', '2026-08-14 09:15:00'),
(4, 'ST-2018', 'Rohan Mehta', 'rohan.mehta@campus.edu', '$2y$10$BL/T7rtupWvJGiqewYckxuzrhfMuM844dcohnQD8NLYVuimecJ3Ky', 'student', 'Electrical Engineering', 'active', '2026-08-14 14:20:00'),
(5, 'ST-2019', 'Sara Khan', 'sara.khan@campus.edu', '$2y$10$BL/T7rtupWvJGiqewYckxuzrhfMuM844dcohnQD8NLYVuimecJ3Ky', 'student', 'Mechanical Engineering', 'active', '2026-08-20 16:45:00')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Insert Complaints
INSERT INTO `complaints` (`id`, `complaint_code`, `user_id`, `title`, `category`, `location`, `description`, `attachment`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CR-1042', 2, 'Classroom fan not working', 'Infrastructure', 'Block B · Room 204', 'The ceiling fan nearest the windows has stopped working. The room becomes uncomfortable during the afternoon lecture.', NULL, 'In Progress', '2026-09-10 10:42:00', '2026-09-11 09:20:00'),
(2, 'CR-1038', 2, 'Library computer issue', 'IT', 'Central Library · Workstation 12', 'Workstation 12 does not boot past the BIOS screen and displays a hard drive error message.', NULL, 'Resolved', '2026-09-08 14:10:00', '2026-09-09 16:30:00'),
(3, 'CR-1031', 2, 'Drinking water problem', 'Facilities', 'Block A · Ground floor', 'The water cooler on the ground floor has very low pressure and the water is not chilling properly.', NULL, 'Pending', '2026-09-06 09:00:00', '2026-09-06 09:00:00'),
(4, 'CR-1019', 5, 'Laboratory equipment problem', 'Laboratory', 'Physics Lab · Bench 6', 'Galvanometer terminal screw is stripped and cannot be tightened for wheatstone bridge experiment.', NULL, 'Pending', '2026-08-30 11:15:00', '2026-08-30 11:15:00');

-- Insert Complaint Updates / History
INSERT INTO `complaint_updates` (`id`, `complaint_id`, `admin_id`, `status`, `remark`, `created_at`) VALUES
(1, 1, 1, 'Submitted', 'Complaint registered in system.', '2026-09-10 10:42:00'),
(2, 1, 1, 'Under Review', 'Reviewed by campus administration office.', '2026-09-10 14:15:00'),
(3, 1, 1, 'In Progress', 'Facilities has scheduled an inspection for today. We will update this request after the repair visit.', '2026-09-11 09:20:00'),
(4, 2, 1, 'Submitted', 'Complaint registered in system.', '2026-09-08 14:10:00'),
(5, 2, 1, 'In Progress', 'IT hardware support dispatched.', '2026-09-08 15:00:00'),
(6, 2, 1, 'Resolved', 'Replaced faulty SATA cable and verified OS boots normally. Workstation 12 is operational.', '2026-09-09 16:30:00'),
(7, 3, 1, 'Submitted', 'Complaint registered in system.', '2026-09-06 09:00:00'),
(8, 4, 1, 'Submitted', 'Complaint registered in system.', '2026-08-30 11:15:00');
