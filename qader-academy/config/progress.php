<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Progress Weighting Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the weight percentages for different course
    | components in the progress calculation system.
    |
    | IMPORTANT: The weights below only account for 30% of the total grade.
    | The remaining 70% is UNDEFINED and awaiting client confirmation.
    |
    | OPEN QUESTION (Section 2.4 of SRS):
    | The original brief states: quizzes = 3%, video completion = 7%, 
    | final exam = 20%. These sum to 30% — the remaining 70% is undefined.
    |
    | TODO: Update these values once client confirms the complete weighting model.
    | Potential allocations for the remaining 70% could include:
    | - Per-chapter quizzes (scaled to fill remainder)
    | - Assignment submissions
    | - Participation metrics
    | - Other assessment types
    |
    */

    // Confirmed weights from client specification
    'quiz_weight' => 3.0,        // 3% of total course grade
    'video_weight' => 7.0,       // 7% of total course grade
    'final_exam_weight' => 20.0, // 20% of total course grade

    // Total defined weight (DO NOT MODIFY - calculated from above)
    'total_defined_weight' => 30.0,

    // Remaining undefined weight (awaiting client confirmation)
    'remaining_undefined_weight' => 70.0,

    /*
    |--------------------------------------------------------------------------
    | Completion Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum progress percentage required to mark a course as completed
    | and trigger certificate generation.
    |
    | Note: Since only 30% is currently tracked, this threshold is set
    | accordingly. Adjust once full weighting is confirmed.
    |
    */
    'completion_threshold' => 30.0,

    /*
    |--------------------------------------------------------------------------
    | Final Exam Gating
    |--------------------------------------------------------------------------
    |
    | Whether the final exam should be gated until all chapters are completed.
    | This can be overridden per-course in the course settings.
    |
    */
    'gate_final_exam' => true,
];
