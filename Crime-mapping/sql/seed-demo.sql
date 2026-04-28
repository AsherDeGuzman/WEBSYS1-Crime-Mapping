-- Demo incidents for La Trinidad crime mapping
-- Run after crime-db.sql

INSERT INTO incidents
    (crime_type_id, title, description, barangay_id, latitude, longitude, occurred_at, severity, status, source, is_public)
VALUES
    (1, 'Assault reported near Balili', 'Witnesses report a physical altercation near the market.', 5, 16.45520000, 120.59010000, '2026-04-27 18:20:00', 'high', 'under_investigation', 'verified', 1),
    (3, 'Theft incident at Pico', 'A small business reported missing equipment.', 11, 16.42510000, 120.59190000, '2026-04-26 10:15:00', 'medium', 'pending', 'reported', 0),
    (6, 'Drug-related report in Poblacion', 'Barangay officials responded to a tip on illicit substances.', 12, 16.44810000, 120.58920000, '2026-04-25 21:30:00', 'high', 'action_taken', 'verified', 1),
    (9, 'Traffic offense near Ambiong', 'Collision reported on the main road, no injuries.', 3, 16.45590000, 120.59370000, '2026-04-24 08:40:00', 'low', 'resolved', 'verified', 1),
    (8, 'Vandalism near Bahong school', 'Graffiti reported on school property.', 4, 16.46980000, 120.56640000, '2026-04-23 19:05:00', 'medium', 'pending', 'reported', 0),
    (4, 'Fraud report from Puguis', 'Suspicious collection activity reported by residents.', 13, 16.45740000, 120.57890000, '2026-04-22 14:10:00', 'medium', 'under_investigation', 'reported', 0),
    (7, 'Online scam complaint in Cruz', 'Resident reported a social media marketplace scam.', 9, 16.46430000, 120.59750000, '2026-04-21 16:45:00', 'medium', 'pending', 'reported', 0),
    (5, 'Public order disturbance in Tawang', 'Noise complaint filed after midnight.', 15, 16.44300000, 120.56950000, '2026-04-20 00:35:00', 'low', 'resolved', 'verified', 1);
