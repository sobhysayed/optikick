<?php

namespace Database\Seeders;

use App\Models\HealthInfoCard;
use Illuminate\Database\Seeder;

class HealthInfoCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            [
                'title' => 'Handwashing Can Protect Your Health',
                'content' => 'Why it matters and tips for how to do it well.',
                'icon_key' => 'hand_wash'
            ],
            [
                'title' => 'Common Concerns About Mental Health',
                'content' => 'Learn about common mental health conditions and what to pay attention to.',
                'icon_key' => 'mental_health'
            ],
            [
                'title' => 'Understanding Your Vitals',
                'content' => 'Certain metrics can give you a sense of how the body is doing.',
                'icon_key' => 'vitals'
            ],
            [
                'title' => 'Why Sleep Is So Important',
                'content' => 'Learn about how sleep helps the body.',
                'icon_key' => 'sleep'
            ],
            [
                'title' => 'Managing Stress Effectively',
                'content' => 'Learn techniques to reduce stress and improve your well-being.',
                'icon_key' => 'stress'
            ],
            [
                'title' => 'Mindfulness for Everyday Life',
                'content' => 'Discover simple ways to stay present and focused.',
                'icon_key' => 'mindfulness'
            ],
            [
                'title' => 'Practicing Gratitude Daily',
                'content' => 'See how gratitude can positively impact your mental health.',
                'icon_key' => 'gratitude'
            ],
            [
                'title' => 'The Role of Breathing in Wellness',
                'content' => 'Learn breathing techniques to calm your body and mind.',
                'icon_key' => 'breathing'
            ],
            [
                'title' => 'Why Sleep Is So Important',
                'content' => 'Learn about how sleep helps the body.',
                'icon_key' => 'sleep'
            ],
            [
                'title' => 'Tips for Better Sleep Tonight',
                'content' => 'Small changes can lead to a restful night.',
                'icon_key' => 'sleep_tips'
            ],
            [
                'title' => 'Creating a Sleep-Friendly Environment',
                'content' => '"Set the stage for quality rest and recovery.',
                'icon_key' => 'sleep_environment'
            ],
            [
                'title' => 'The Power of Naps',
                'content' => 'Short naps can boost mood and mental clarity.',
                'icon_key' => 'nap'
            ],
            [
                'title' => 'The Importance of Regular Exercise',
                'content' => 'How movement supports your body and mind.',
                'icon_key' => 'exercise'
            ],
            [
                'title' => 'Quick Workouts You Can Do Anywhere',
                'content' => 'No gym? No problem. Get moving with minimal space.',
                'icon_key' => 'quick_workout'
            ],
            [
                'title' => 'Cardio vs Strength Training',
                'content' => 'Understand the difference and how both benefit your body.',
                'icon_key' => 'cardio_vs_strength'
            ],
            [
                'title' => 'Benefits of Daily Stretching',
                'content' => 'Improve flexibility and reduce tension with regular stretches.',
                'icon_key' => 'stretching'
            ],
            [
                'title' => 'Hydration and Your Body',
                'content' => 'Why staying hydrated is essential for optimal health.',
                'icon_key' => 'hydration'
            ],
            [
                'title' => 'Healthy Snacking Tips',
                'content' => 'Choose snacks that fuel your body and mind.',
                'icon_key' => 'snacking'
            ],
            [
                'title' => 'Meal Prep for a Healthier Week',
                'content' => 'Save time and eat well with these planning tips.',
                'icon_key' => 'meal_prep'
            ],
            [
                'title' => 'Understanding Food Labels',
                'content' => 'Decode what’s in your food and make smarter choices.',
                'icon_key' => 'food_labels'
            ],
            [
                'title' => 'Balanced Eating for Energy',
                'content' => 'Learn how to eat to feel your best throughout the day.',
                'icon_key' => 'balanced_eating'
            ],
            [
                'title' => 'Sugar and Its Hidden Sources',
                'content' => 'Watch out for added sugars in common foods.',
                'icon_key' => 'sugar_alert'
            ],
            [
                'title' => 'Understanding Your Vitals',
                'content' => 'Certain metrics can give you a sense of how the body is doing.',
                'icon_key' => 'vitals'
            ],
            [
                'title' => 'Tracking Your Heart Rate',
                'content' => 'Understand what your heart is telling you.',
                'icon_key' => 'heart_rate'
            ],
            [
                'title' => 'What Your Blood Pressure Means',
                'content' => 'Know your numbers and what they say about your health.',
                'icon_key' => 'blood_pressure'
            ],
            [
                'title' => 'Spotting Early Signs of Illness',
                'content' => 'Pay attention to small changes in your body.',
                'icon_key' => 'illness_signs'
            ],
            [
                'title' => 'Listening to Your Body’s Signals',
                'content' => 'Tune in to fatigue, hunger, and pain cues.',
                'icon_key' => 'body_signals'
            ],
            [
                'title' => 'Healthy Morning Routines',
                'content' => 'Start your day with habits that energize and focus your mind.',
                'icon_key' => 'morning_routine'
            ],
            [
                'title' => 'Recognizing Burnout Early',
                'content' => 'Understand the signs of burnout and how to prevent it.',
                'icon_key' => 'burnout'
            ],
            [
                'title' => 'The Role of Fiber in Digestion',
                'content' => 'Improve gut health with fiber-rich foods and smart choices.',
                'icon_key' => 'fiber'
            ]
        ];

        foreach ($cards as $card) {
            HealthInfoCard::create($card);
        }
    }
}