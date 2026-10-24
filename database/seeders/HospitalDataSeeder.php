<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Database\Seeder;

class HospitalDataSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Cardiology & Vascular Medicine',
                'slug' => 'cardiology',
                'icon' => 'bi-heart-pulse',
                'summary' => 'Comprehensive cardiac care including advanced diagnostics, non-invasive imaging, and cardiac rehabilitation.',
                'description' => 'Our Department of Cardiology provides cutting-edge diagnostics and interventional therapies for all stages of heart disease. Equipped with a state-of-the-art cardiac catheterization lab and 24/7 cardiac emergency response, our board-certified cardiologists deliver personalized care for arrhythmias, coronary artery disease, heart failure, and structural heart defects.',
                'services' => [
                    'Echocardiography & Stress Testing',
                    'Coronary Angiography & Stenting',
                    'Pacemaker Implantation & Monitoring',
                    'Heart Failure Disease Management',
                    '24/7 Emergency STEMI Response',
                ],
                'head_of_department' => 'Dr. Marcus Vance, MD, FACC',
                'phone_extension' => 'Ext. 2101',
                'emergency_contact' => '+254 700 911 101',
                'image' => 'assets/img/health/cardiology-2.webp',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Neurology & Brain Sciences',
                'slug' => 'neurology',
                'icon' => 'bi-activity',
                'summary' => 'Specialized neurological diagnostic evaluations and long-term treatment for complex brain and nervous system disorders.',
                'description' => 'The Neurology Department at MediCare offers multidisciplinary treatment for conditions affecting the brain, spinal cord, peripheral nerves, and muscles. From rapid stroke intervention to long-term management of epilepsy, multiple sclerosis, and neurodegenerative conditions, our specialists leverage 3T MRI, digital EEG, and specialized neuromonitoring.',
                'services' => [
                    'Comprehensive Stroke Center Care',
                    'Digital Electroencephalography (EEG)',
                    'Epilepsy Monitoring & Medical Management',
                    'Memory Disorders & Dementia Clinic',
                    'Parkinson’s Disease & Movement Disorders',
                ],
                'head_of_department' => 'Dr. Amina Patel, MD, FAAN',
                'phone_extension' => 'Ext. 2205',
                'emergency_contact' => '+254 700 911 102',
                'image' => 'assets/img/health/neurology-3.webp',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pediatrics & Neonatal Care',
                'slug' => 'pediatrics',
                'icon' => 'bi-emoji-smile',
                'summary' => 'Compassionate care for infants, children, and adolescents with Level III Neonatal Intensive Care (NICU).',
                'description' => 'Our Department of Pediatrics is dedicated to the health and development of children from birth through adolescence. With a dedicated pediatric ward, Level III NICU, and child-friendly outpatient clinics, we prioritize comfort, clinical excellence, and family-centered care for routine developmental milestones and acute illnesses.',
                'services' => [
                    'Level III Neonatal Intensive Care (NICU)',
                    'Well-Child Checks & Immunizations',
                    'Pediatric Emergency Response',
                    'Childhood Asthma & Allergy Clinic',
                    'Pediatric Infectious Disease Care',
                ],
                'head_of_department' => 'Dr. Elizabeth Moraa, MBChB, MMed (Peds)',
                'phone_extension' => 'Ext. 2310',
                'emergency_contact' => '+254 700 911 103',
                'image' => 'assets/img/health/pediatrics-4.webp',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Orthopedics & Joint Reconstruction',
                'slug' => 'orthopedics',
                'icon' => 'bi-shield-plus',
                'summary' => 'Minimally invasive joint replacements, sports injury rehabilitation, and complex spinal reconstruction.',
                'description' => 'The Orthopedics Department specializes in the prevention, diagnosis, and surgical treatment of musculoskeletal disorders. Our orthopedic surgeons utilize computer-navigated arthroscopy, robotic-assisted joint replacement, and advanced physical therapy protocols to restore mobility and alleviate pain.',
                'services' => [
                    'Total Hip & Knee Replacement',
                    'Sports Medicine & Arthroscopy',
                    'Spine Decompression & Stabilization',
                    'Complex Fracture Reconstruction',
                    'Dedicated Orthopedic Physical Therapy',
                ],
                'head_of_department' => 'Dr. David Mwangi, MBChB, FCS (ECSA)',
                'phone_extension' => 'Ext. 2415',
                'emergency_contact' => '+254 700 911 104',
                'image' => 'assets/img/health/orthopedics-1.webp',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Oncology & Cancer Center',
                'slug' => 'oncology',
                'icon' => 'bi-capsule',
                'summary' => 'Integrated medical oncology, targeted chemotherapy, immunotherapy, and dedicated patient survivorship support.',
                'description' => 'The MediCare Cancer Center provides comprehensive cancer care guided by multidisciplinary tumor boards. Our services encompass early screening, precise histopathology, chemotherapy infusion suites, targeted molecular therapies, and palliative support designed around holistic patient well-being.',
                'services' => [
                    'Outpatient Chemotherapy Infusion',
                    'Targeted Immunotherapy & Biologicals',
                    'Multidisciplinary Tumor Board Consultation',
                    'Early Cancer Screening & Genetic Risk',
                    'Nutritional & Palliative Supportive Care',
                ],
                'head_of_department' => 'Dr. Sarah Jenkins, MD, PhD',
                'phone_extension' => 'Ext. 2520',
                'emergency_contact' => '+254 700 911 105',
                'image' => 'assets/img/health/oncology-2.webp',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'General Surgery & Minimally Invasive Procedures',
                'slug' => 'general-surgery',
                'icon' => 'bi-scissors',
                'summary' => 'Advanced laparoscopic operations, gastrointestinal interventions, and emergency surgical care.',
                'description' => 'The Department of General Surgery delivers high-precision surgical intervention across outpatient day-surgery and complex inpatient cases. We prioritize laparoscopic and minimally invasive techniques to minimize postoperative pain, reduce infection risk, and accelerate recovery times.',
                'services' => [
                    'Advanced Laparoscopic Cholecystectomy & Hernia',
                    'Gastrointestinal & Colorectal Surgery',
                    'Thyroid & Endocrine Surgeries',
                    'Emergency Trauma Surgical Operations',
                    'Same-Day Ambulatory Surgery Center',
                ],
                'head_of_department' => 'Dr. Robert Chen, MD, FACS',
                'phone_extension' => 'Ext. 2630',
                'emergency_contact' => '+254 700 911 106',
                'image' => 'assets/img/health/surgery-2.webp',
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($departments as $deptData) {
            $dept = Department::updateOrCreate(
                ['slug' => $deptData['slug']],
                $deptData
            );

            // Seed doctors for each department
            if ($dept->slug === 'cardiology') {
                Doctor::updateOrCreate(
                    ['slug' => 'dr-marcus-vance'],
                    [
                        'department_id' => $dept->id,
                        'name' => 'Dr. Marcus Vance, MD, FACC',
                        'slug' => 'dr-marcus-vance',
                        'specialty' => 'Senior Consultant Interventional Cardiologist',
                        'qualifications' => 'MD (Harvard), FACC, FSCAI',
                        'experience_years' => 18,
                        'bio' => 'Dr. Marcus Vance is the Chief of Cardiology with over 18 years of clinical experience in coronary interventions, structural heart diseases, and preventive cardiology. He has performed over 3,500 successful catheterizations.',
                        'languages' => 'English, French',
                        'consultation_days' => 'Monday, Wednesday, Friday',
                        'consultation_hours' => '09:00 AM - 01:00 PM',
                        'room_number' => 'Suite 302',
                        'rating' => 4.9,
                        'review_count' => 184,
                        'image' => 'assets/img/health/staff-1.webp',
                        'is_available' => true,
                    ]
                );
            } elseif ($dept->slug === 'neurology') {
                Doctor::updateOrCreate(
                    ['slug' => 'dr-amina-patel'],
                    [
                        'department_id' => $dept->id,
                        'name' => 'Dr. Amina Patel, MD, FAAN',
                        'slug' => 'dr-amina-patel',
                        'specialty' => 'Consultant Neurologist & Stroke Specialist',
                        'qualifications' => 'MD (Johns Hopkins), Fellowship in Neurovascular Care',
                        'experience_years' => 14,
                        'bio' => 'Dr. Amina Patel leads our Comprehensive Stroke Care program. She is renowned for rapid neuro-intervention protocols, epilepsy management, and neuromuscular clinical research.',
                        'languages' => 'English, Swahili, Hindi',
                        'consultation_days' => 'Tuesday, Thursday, Saturday',
                        'consultation_hours' => '10:00 AM - 02:00 PM',
                        'room_number' => 'Suite 405',
                        'rating' => 4.8,
                        'review_count' => 142,
                        'image' => 'assets/img/health/staff-3.webp',
                        'is_available' => true,
                    ]
                );
            } elseif ($dept->slug === 'pediatrics') {
                Doctor::updateOrCreate(
                    ['slug' => 'dr-elizabeth-moraa'],
                    [
                        'department_id' => $dept->id,
                        'name' => 'Dr. Elizabeth Moraa, MBChB, MMed',
                        'slug' => 'dr-elizabeth-moraa',
                        'specialty' => 'Consultant Pediatrician & Neonatologist',
                        'qualifications' => 'MBChB (UoN), MMed Pediatrics, Fellowship in Neonatology',
                        'experience_years' => 12,
                        'bio' => 'Dr. Elizabeth Moraa specializes in high-risk neonatal care, premature infant stabilization, and developmental pediatrics. She directs the Level III NICU with warmth and precision.',
                        'languages' => 'English, Swahili',
                        'consultation_days' => 'Monday through Friday',
                        'consultation_hours' => '08:30 AM - 12:30 PM',
                        'room_number' => 'Suite 108',
                        'rating' => 5.0,
                        'review_count' => 210,
                        'image' => 'assets/img/health/staff-7.webp',
                        'is_available' => true,
                    ]
                );
            } elseif ($dept->slug === 'orthopedics') {
                Doctor::updateOrCreate(
                    ['slug' => 'dr-david-mwangi'],
                    [
                        'department_id' => $dept->id,
                        'name' => 'Dr. David Mwangi, MBChB, FCS',
                        'slug' => 'dr-david-mwangi',
                        'specialty' => 'Consultant Orthopedic & Arthroscopic Surgeon',
                        'qualifications' => 'MBChB, FCS (ECSA Ortho), Sports Medicine Fellowship',
                        'experience_years' => 15,
                        'bio' => 'Dr. David Mwangi has extensive expertise in minimally invasive joint preservation, ACL reconstructions, and robotic-assisted total hip and knee arthroplasty.',
                        'languages' => 'English, Swahili',
                        'consultation_days' => 'Monday, Tuesday, Thursday',
                        'consultation_hours' => '09:00 AM - 03:00 PM',
                        'room_number' => 'Suite 214',
                        'rating' => 4.9,
                        'review_count' => 165,
                        'image' => 'assets/img/health/staff-9.webp',
                        'is_available' => true,
                    ]
                );
            } elseif ($dept->slug === 'oncology') {
                Doctor::updateOrCreate(
                    ['slug' => 'dr-sarah-jenkins'],
                    [
                        'department_id' => $dept->id,
                        'name' => 'Dr. Sarah Jenkins, MD, PhD',
                        'slug' => 'dr-sarah-jenkins',
                        'specialty' => 'Consultant Medical Oncologist',
                        'qualifications' => 'MD, PhD in Molecular Oncology (Oxford), ESMO Certified',
                        'experience_years' => 16,
                        'bio' => 'Dr. Sarah Jenkins is an internationally published medical oncologist pioneering personalized genomic therapies and targeted immunotherapies for solid tumors.',
                        'languages' => 'English, German',
                        'consultation_days' => 'Tuesday, Wednesday, Friday',
                        'consultation_hours' => '11:00 AM - 04:00 PM',
                        'room_number' => 'Suite 501',
                        'rating' => 4.9,
                        'review_count' => 138,
                        'image' => 'assets/img/health/staff-5.webp',
                        'is_available' => true,
                    ]
                );
            } elseif ($dept->slug === 'general-surgery') {
                Doctor::updateOrCreate(
                    ['slug' => 'dr-robert-chen'],
                    [
                        'department_id' => $dept->id,
                        'name' => 'Dr. Robert Chen, MD, FACS',
                        'slug' => 'dr-robert-chen',
                        'specialty' => 'Chief of Surgery & Laparoscopic Specialist',
                        'qualifications' => 'MD, FACS, Advanced Laparoscopy Fellowship',
                        'experience_years' => 20,
                        'bio' => 'Dr. Robert Chen oversees all surgical operating suites at MediCare, with special mastery in single-incision laparoscopic surgery and complex abdominal wall reconstructions.',
                        'languages' => 'English, Mandarin',
                        'consultation_days' => 'Monday, Wednesday, Thursday',
                        'consultation_hours' => '08:00 AM - 12:00 PM',
                        'room_number' => 'Suite 201',
                        'rating' => 4.8,
                        'review_count' => 195,
                        'image' => 'assets/img/health/staff-12.webp',
                        'is_available' => true,
                    ]
                );
            }
        }
    }
}
