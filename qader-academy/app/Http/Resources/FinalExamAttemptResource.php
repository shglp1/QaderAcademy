<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinalExamAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * NOTE: Model answers for written questions are hidden until graded.
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isTrainer = $user && $user->hasRole(['trainer', 'admin', 'super_admin']);

        return [
            'id' => $this->id,
            'final_exam_id' => $this->final_exam_id,
            'student_id' => $this->student_id,
            'student_name' => $this->student?->name,
            'status' => $this->status,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'submitted_at' => $this->created_at->toIso8601String(),
            'graded_at' => $this->graded_at?->toIso8601String(),
            'grader_name' => $this->grader?->name,
            'feedback' => $isTrainer || $this->status === 'graded' ? $this->feedback : null,
            'answers' => $this->whenLoaded('answerItems', function () use ($isTrainer) {
                return $this->answerItems->map(function ($answer) use ($isTrainer) {
                $result = [
                    'question_id' => $answer->question_id,
                    'student_answer' => $answer->student_answer,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                ];

                // Only show model answer if trainer/admin or graded
                if ($isTrainer || $this->status === 'graded') {
                    $result['model_answer'] = $answer->question->correct_answer;
                }

                return $result;
                });
            }, []),
        ];
    }
}
