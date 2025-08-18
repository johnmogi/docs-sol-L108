# Student-Teacher Relationships and School Classes

## Current Implementation

### Database Structure

#### 1. User Relationships
- **Table**: `edc_usermeta`
  - `user_id`: Student's WordPress user ID
  - `meta_key`: 
    - `school_teacher_id`: ID of the assigned teacher
    - `assigned_teacher`: ID of the assigned teacher (duplicate of school_teacher_id)

#### 2. Example: User 347 (Anthony Larsen)
```sql
-- Student's teacher assignment
SELECT * FROM edc_usermeta 
WHERE user_id = 347 
AND (meta_key = 'school_teacher_id' OR meta_key = 'assigned_teacher');

-- Result:
-- +----------+---------+-------------------+------------+
-- | umeta_id | user_id | meta_key          | meta_value |
-- +----------+---------+-------------------+------------+
-- |    10868 |     347 | school_teacher_id | 316        |
-- |    10869 |     347 | assigned_teacher  | 316        |
-- +----------+---------+-------------------+------------+
```

### Current Issues
1. **Incomplete Implementation**: 
   - The system has tables for school classes (`edc_school_classes`) and student enrollments (`edc_school_student_classes`), but they don't appear to be actively used
   - Teacher-student relationships are stored in user meta instead of the dedicated tables

2. **Missing Course Connections**:
   - Teacher 316 (David Cohen) doesn't have any courses or quizzes assigned
   - Student 347 (Anthony Larsen) has access to course 898, but no clear connection to teacher 316

## Recommended Structure

### 1. Teacher-Student Relationships
```sql
-- Current implementation (in use)
UPDATE edc_usermeta 
SET meta_value = [teacher_id] 
WHERE user_id = [student_id] 
AND meta_key IN ('school_teacher_id', 'assigned_teacher');

-- Recommended implementation (not in use)
INSERT INTO edc_school_student_classes (student_id, class_id, created_at)
VALUES ([student_id], [class_id], NOW());
```

### 2. Course Assignment
```sql
-- Assign teacher to course
INSERT INTO edc_postmeta (post_id, meta_key, meta_value)
VALUES ([course_id], '_ld_teacher', [teacher_id]);

-- Assign student to course (LearnDash)
INSERT INTO edc_learndash_user_activity 
(user_id, course_id, activity_type, activity_status, activity_started)
VALUES 
([student_id], [course_id], 'course', 1, UNIX_TIMESTAMP());
```

## Next Steps

1. **Standardize Relationship Storage**:
   - Choose between using `usermeta` or dedicated tables, not both
   - Update all code to use the chosen method consistently

2. **Teacher Dashboard**:
   - Add functionality for teachers to create and manage quizzes
   - Implement class management for teachers

3. **Student View**:
   - Show assigned teacher's quizzes/courses
   - Display progress and grades for teacher-assigned work

4. **Documentation**:
   - Update API documentation to reflect the chosen relationship structure
   - Document any API endpoints for managing these relationships

## Example Queries

### Get All Students for a Teacher
```sql
-- Using current implementation
SELECT u.ID, u.display_name, u.user_email
FROM edc_users u
JOIN edc_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'school_teacher_id' 
AND um.meta_value = [teacher_id];
```

### Get All Courses for a Teacher
```sql
SELECT p.ID, p.post_title
FROM edc_posts p
JOIN edc_postmeta pm ON p.ID = pm.post_id
WHERE pm.meta_key = '_ld_teacher'
AND pm.meta_value = [teacher_id]
AND p.post_type = 'sfwd-courses';
```

## Data Flow

1. **Teacher Assignment**:
   - Admin assigns teacher to student via user meta
   - System stores teacher ID in student's meta

2. **Course Creation**:
   - Teacher creates courses/quizzes
   - System links teacher to courses via postmeta

3. **Student Access**:
   - System shows teacher's courses to assigned students
   - Student activity is tracked and visible to teacher

## Troubleshooting

### Student Can't See Teacher's Quizzes
1. Verify teacher-student relationship exists in `edc_usermeta`
2. Check if teacher has any published courses (`post_type = 'sfwd-courses'`)
3. Verify courses are assigned to teacher in `edc_postmeta`
4. Check if student is enrolled in the course (`edc_learndash_user_activity`)
