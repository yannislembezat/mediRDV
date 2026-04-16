<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Appointment;
use App\Entity\Availability;
use App\Entity\Consultation;
use App\Entity\MedecinProfile;
use App\Entity\Medication;
use App\Entity\Notification;
use App\Entity\Prescription;
use App\Entity\PrescriptionItem;
use App\Entity\Specialty;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Enum\Gender;
use App\Enum\MedicationForm;
use App\Enum\NotificationType;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    private const SPECIALTIES = [
        ['name' => 'Médecine Générale', 'slug' => 'medecine-generale', 'icon' => 'stethoscope', 'description' => 'Consultations générales et suivi médical', 'displayOrder' => 1],
        ['name' => 'Cardiologie', 'slug' => 'cardiologie', 'icon' => 'heart-pulse', 'description' => 'Maladies du cœur et des vaisseaux', 'displayOrder' => 2],
        ['name' => 'Dermatologie', 'slug' => 'dermatologie', 'icon' => 'hand-index-thumb', 'description' => 'Maladies de la peau, cheveux et ongles', 'displayOrder' => 3],
        ['name' => 'Ophtalmologie', 'slug' => 'ophtalmologie', 'icon' => 'eye', 'description' => 'Maladies des yeux et de la vision', 'displayOrder' => 4],
        ['name' => 'Pédiatrie', 'slug' => 'pediatrie', 'icon' => 'balloon-heart', 'description' => 'Médecine des enfants et adolescents', 'displayOrder' => 5],
        ['name' => 'Gynécologie', 'slug' => 'gynecologie', 'icon' => 'gender-female', 'description' => 'Santé de la femme et suivi grossesse', 'displayOrder' => 6],
        ['name' => 'Orthopédie', 'slug' => 'orthopedie', 'icon' => 'person-standing', 'description' => 'Maladies des os, articulations et muscles', 'displayOrder' => 7],
        ['name' => 'ORL', 'slug' => 'orl', 'icon' => 'ear', 'description' => 'Oreilles, nez et gorge', 'displayOrder' => 8],
        ['name' => 'Neurologie', 'slug' => 'neurologie', 'icon' => 'activity', 'description' => 'Maladies du système nerveux', 'displayOrder' => 9],
        ['name' => 'Dentisterie', 'slug' => 'dentisterie', 'icon' => 'emoji-smile', 'description' => 'Soins dentaires et chirurgie buccale', 'displayOrder' => 10],
        ['name' => 'Urologie', 'slug' => 'urologie', 'icon' => 'droplet', 'description' => 'Appareil urinaire et génital masculin', 'displayOrder' => 11],
        ['name' => 'Pneumologie', 'slug' => 'pneumologie', 'icon' => 'lungs', 'description' => 'Maladies des poumons et voies respiratoires', 'displayOrder' => 12],
    ];

    private const MEDICATIONS = [
        ['name' => 'Doliprane', 'genericName' => 'Paracétamol', 'defaultDosage' => '1000mg - 3x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Antalgique'],
        ['name' => 'Augmentin', 'genericName' => 'Amoxicilline/Ac. clav.', 'defaultDosage' => '1g - 2x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Antibiotique'],
        ['name' => 'Voltarène', 'genericName' => 'Diclofénac', 'defaultDosage' => '50mg - 2x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Anti-inflammatoire'],
        ['name' => 'Amoxicilline', 'genericName' => 'Amoxicilline', 'defaultDosage' => '500mg - 3x/jour', 'form' => MedicationForm::GELULE, 'category' => 'Antibiotique'],
        ['name' => 'Oméprazole', 'genericName' => 'Oméprazole', 'defaultDosage' => '20mg - 1x/jour', 'form' => MedicationForm::GELULE, 'category' => 'Anti-ulcéreux'],
        ['name' => 'Amlodipine', 'genericName' => 'Amlodipine', 'defaultDosage' => '5mg - 1x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Antihypertenseur'],
        ['name' => 'Metformine', 'genericName' => 'Metformine', 'defaultDosage' => '850mg - 2x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Antidiabétique'],
        ['name' => 'Ventoline', 'genericName' => 'Salbutamol', 'defaultDosage' => '2 bouffées si besoin', 'form' => MedicationForm::AUTRE, 'category' => 'Bronchodilatateur'],
        ['name' => 'Vitamine D3', 'genericName' => 'Cholécalciférol', 'defaultDosage' => '1000 UI/jour', 'form' => MedicationForm::GOUTTES, 'category' => 'Vitamine'],
        ['name' => 'Magnésium B6', 'genericName' => 'Magnésium + Pyridoxine', 'defaultDosage' => '2 cp/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Supplément'],
        ['name' => 'Ibuprofène', 'genericName' => 'Ibuprofène', 'defaultDosage' => '400mg - 3x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Anti-inflammatoire'],
        ['name' => 'Loratadine', 'genericName' => 'Loratadine', 'defaultDosage' => '10mg - 1x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Antihistaminique'],
        ['name' => 'Azithromycine', 'genericName' => 'Azithromycine', 'defaultDosage' => '500mg - 1x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Antibiotique'],
        ['name' => 'Bisoprolol', 'genericName' => 'Bisoprolol', 'defaultDosage' => '5mg - 1x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Bêtabloquant'],
        ['name' => 'Simvastatine', 'genericName' => 'Simvastatine', 'defaultDosage' => '20mg - 1x/soir', 'form' => MedicationForm::COMPRIME, 'category' => 'Hypolipémiant'],
        ['name' => 'Pantoprazole', 'genericName' => 'Pantoprazole', 'defaultDosage' => '40mg - 1x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Anti-ulcéreux'],
        ['name' => 'Prednisolone', 'genericName' => 'Prednisolone', 'defaultDosage' => '20mg - 1x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Corticoïde'],
        ['name' => 'Cétirizine', 'genericName' => 'Cétirizine', 'defaultDosage' => '10mg - 1x/soir', 'form' => MedicationForm::COMPRIME, 'category' => 'Antihistaminique'],
        ['name' => 'Acide folique', 'genericName' => 'Acide folique', 'defaultDosage' => '5mg - 1x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Vitamine'],
        ['name' => 'Fer ferreux', 'genericName' => 'Sulfate ferreux', 'defaultDosage' => '80mg - 1x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Supplément'],
        ['name' => 'Salbutamol', 'genericName' => 'Salbutamol', 'defaultDosage' => '4mg - 3x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Bronchodilatateur'],
        ['name' => 'Codéine', 'genericName' => 'Phosphate de codéine', 'defaultDosage' => '30mg - 3x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'Antalgique opioïde'],
        ['name' => 'Doxycycline', 'genericName' => 'Doxycycline', 'defaultDosage' => '100mg - 2x/jour', 'form' => MedicationForm::GELULE, 'category' => 'Antibiotique'],
        ['name' => 'Tramadol', 'genericName' => 'Tramadol', 'defaultDosage' => '50mg - 3x/jour', 'form' => MedicationForm::GELULE, 'category' => 'Antalgique opioïde'],
        ['name' => 'Enalapril', 'genericName' => 'Enalapril', 'defaultDosage' => '10mg - 1x/jour', 'form' => MedicationForm::COMPRIME, 'category' => 'IEC antihypertenseur'],
    ];

    private const DOCTORS = [
        [
            'email' => 'fatima.zohra@medirdv.fr',
            'firstName' => 'Fatima',
            'lastName' => 'Zohra',
            'phone' => '+33600000101',
            'gender' => Gender::FEMALE,
            'specialty' => 'cardiologie',
            'bio' => 'Cardiologue installée à Paris, spécialisée dans la prévention cardiovasculaire et le suivi des patients chroniques.',
            'duration' => 30,
            'office' => 'Paris 15e - Cabinet 201',
            'experience' => 12,
            'diploma' => 'DES de Cardiologie - Université Paris Cité',
            'availability' => [
                ['dayOfWeek' => 1, 'start' => '09:00', 'end' => '12:00'],
                ['dayOfWeek' => 3, 'start' => '14:00', 'end' => '17:30'],
            ],
        ],
        [
            'email' => 'youssef.bennani@medirdv.fr',
            'firstName' => 'Youssef',
            'lastName' => 'Bennani',
            'phone' => '+33600000102',
            'gender' => Gender::MALE,
            'specialty' => 'medecine-generale',
            'bio' => 'Médecin généraliste à Lyon, orienté prévention, suivi des pathologies chroniques et coordination des soins.',
            'duration' => 20,
            'office' => 'Lyon 3e - Maison de santé Part-Dieu',
            'experience' => 8,
            'diploma' => 'Doctorat en Médecine - Université Claude Bernard Lyon 1',
            'availability' => [
                ['dayOfWeek' => 2, 'start' => '08:30', 'end' => '12:30'],
                ['dayOfWeek' => 4, 'start' => '13:30', 'end' => '17:30'],
            ],
        ],
        [
            'email' => 'marie.dubois@medirdv.fr',
            'firstName' => 'Marie',
            'lastName' => 'Dubois',
            'phone' => '+33600000103',
            'gender' => Gender::FEMALE,
            'specialty' => 'dermatologie',
            'bio' => 'Dermatologue à Bordeaux, en consultation de dermatologie clinique et suivi des affections cutanées courantes.',
            'duration' => 25,
            'office' => 'Bordeaux Centre - Cabinet 3',
            'experience' => 10,
            'diploma' => 'DES de Dermatologie - Université de Bordeaux',
            'availability' => [
                ['dayOfWeek' => 1, 'start' => '10:00', 'end' => '13:00'],
                ['dayOfWeek' => 5, 'start' => '09:00', 'end' => '12:00'],
            ],
        ],
    ];

    private const PATIENTS = [
        ['email' => 'ahmed.benali@example.fr', 'firstName' => 'Ahmed', 'lastName' => 'Benali', 'phone' => '+33612345601', 'gender' => Gender::MALE, 'dateOfBirth' => '1990-05-15', 'address' => 'Paris 15e'],
        ['email' => 'salma.alaoui@example.fr', 'firstName' => 'Salma', 'lastName' => 'Alaoui', 'phone' => '+33612345602', 'gender' => Gender::FEMALE, 'dateOfBirth' => '1994-11-02', 'address' => 'Lyon 3e'],
    ];

    private const APPOINTMENTS = [
        [
            'p' => 1,
            'd' => 1,
            'daysOffset' => -12,
            'hour' => 9,
            'status' => AppointmentStatus::COMPLETED,
            'reason' => 'Fièvre, maux de gorge et fatigue depuis 3 jours',
            'adminNote' => 'Consultation effectuée au cabinet',
            'diagnosis' => 'Rhinopharyngite aiguë virale',
            'notes' => 'Examen clinique rassurant. Repos, hydratation et traitement symptomatique prescrits.',
            'vitals' => ['température' => '38,2 °C', 'pouls' => '88 bpm', 'satO2' => '98 %'],
            'rx' => [
                ['med' => 'Doliprane', 'dosage' => '1000mg - 3x/jour', 'duration' => '4 jours', 'frequency' => 'Toutes les 8h si fièvre', 'instructions' => 'Ne pas dépasser 3 prises par jour'],
                ['med' => 'Loratadine', 'dosage' => '10mg - 1x/jour', 'duration' => '5 jours', 'frequency' => 'Le soir', 'instructions' => 'À prendre en cas de gêne ORL persistante'],
            ],
        ],
        [
            'p' => 1,
            'd' => 0,
            'daysOffset' => 5,
            'hour' => 10,
            'status' => AppointmentStatus::CONFIRMED,
            'reason' => 'Bilan cardiologique préventif avec antécédents familiaux',
            'adminNote' => 'ECG précédent à apporter',
        ],
        [
            'p' => 0,
            'd' => 2,
            'daysOffset' => -6,
            'hour' => 10,
            'status' => AppointmentStatus::CANCELLED,
            'reason' => 'Avis dermatologique pour irritation cutanée au niveau des mains',
            'adminNote' => 'Annulation demandée par le patient',
        ],
        [
            'p' => 0,
            'd' => 0,
            'daysOffset' => -18,
            'hour' => 11,
            'status' => AppointmentStatus::REFUSED,
            'reason' => 'Palpitations intermittentes et essoufflement léger à l effort',
            'adminNote' => 'Créneau non disponible, merci de sélectionner un autre horaire',
        ],
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $specialties = $this->loadSpecialties($manager);
        $medications = $this->loadMedications($manager);
        $admin = $this->loadAdmin($manager);
        $patients = $this->loadPatients($manager);
        $doctors = $this->loadDoctors($manager, $specialties);

        $manager->flush();

        $this->loadAppointments($manager, $patients, $doctors, $admin, $medications);

        $manager->flush();
    }

    /** @return array<string, Specialty> */
    private function loadSpecialties(ObjectManager $manager): array
    {
        $specialties = [];

        foreach (self::SPECIALTIES as $data) {
            $specialty = (new Specialty())
                ->setName($data['name'])
                ->setSlug($data['slug'])
                ->setIcon($data['icon'])
                ->setDescription($data['description'])
                ->setDisplayOrder($data['displayOrder']);

            $manager->persist($specialty);
            $specialties[$data['slug']] = $specialty;
        }

        return $specialties;
    }

    /** @return array<string, Medication> */
    private function loadMedications(ObjectManager $manager): array
    {
        $medications = [];

        foreach (self::MEDICATIONS as $data) {
            $medication = (new Medication())
                ->setName($data['name'])
                ->setGenericName($data['genericName'])
                ->setDefaultDosage($data['defaultDosage'])
                ->setForm($data['form'])
                ->setCategory($data['category']);

            $manager->persist($medication);
            $medications[$data['name']] = $medication;
        }

        return $medications;
    }

    private function loadAdmin(ObjectManager $manager): User
    {
        $admin = (new User())
            ->setEmail('admin@medirdv.fr')
            ->setFirstName('Admin')
            ->setLastName('MediRDV')
            ->setRoles([UserRole::ADMIN->value])
            ->setIsActive(true);

        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        return $admin;
    }

    /** @return User[] indexed 0-1 */
    private function loadPatients(ObjectManager $manager): array
    {
        $patients = [];

        foreach (self::PATIENTS as $data) {
            $patient = (new User())
                ->setEmail($data['email'])
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setPhone($data['phone'])
                ->setGender($data['gender'])
                ->setAddress($data['address'])
                ->setDateOfBirth(new \DateTimeImmutable($data['dateOfBirth']))
                ->setRoles([UserRole::PATIENT->value])
                ->setIsActive(true);

            $patient->setPassword($this->passwordHasher->hashPassword($patient, 'patient123'));
            $manager->persist($patient);
            $patients[] = $patient;
        }

        return $patients;
    }

    /**
     * @param array<string, Specialty> $specialties
     * @return MedecinProfile[] indexed 0-2
     */
    private function loadDoctors(ObjectManager $manager, array $specialties): array
    {
        $doctors = [];

        foreach (self::DOCTORS as $data) {
            $doctor = (new User())
                ->setEmail($data['email'])
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setPhone($data['phone'])
                ->setGender($data['gender'])
                ->setRoles([UserRole::MEDECIN->value])
                ->setIsActive(true);

            $doctor->setPassword($this->passwordHasher->hashPassword($doctor, 'doctor123'));

            $profile = (new MedecinProfile())
                ->setUser($doctor)
                ->setSpecialty($specialties[$data['specialty']])
                ->setBio($data['bio'])
                ->setConsultationDuration($data['duration'])
                ->setOfficeLocation($data['office'])
                ->setYearsExperience($data['experience'])
                ->setDiploma($data['diploma']);

            foreach ($data['availability'] as $avData) {
                $availability = (new Availability())
                    ->setStartTime($this->createTime($avData['start']))
                    ->setEndTime($this->createTime($avData['end']));

                if (isset($avData['dayOfWeek'])) {
                    $availability
                        ->setIsRecurring(true)
                        ->setDayOfWeek($avData['dayOfWeek'])
                        ->setSpecificDate(null);
                } else {
                    $availability
                        ->setIsRecurring(false)
                        ->setDayOfWeek(null)
                        ->setSpecificDate(new \DateTimeImmutable($avData['specificDate']));
                }

                $profile->addAvailability($availability);
                $manager->persist($availability);
            }

            $manager->persist($doctor);
            $manager->persist($profile);
            $doctors[] = $profile;
        }

        return $doctors;
    }

    /**
     * @param User[] $patients
     * @param MedecinProfile[] $doctors
     * @param array<string, Medication> $medications
     */
    private function loadAppointments(
        ObjectManager $manager,
        array $patients,
        array $doctors,
        User $admin,
        array $medications,
    ): void {
        $today = new \DateTimeImmutable('today');

        foreach (self::APPOINTMENTS as $def) {
            $patient = $patients[$def['p']];
            $doctor = $doctors[$def['d']];
            $status = $def['status'];

            $apptDate = $today->modify(sprintf('%+d days', $def['daysOffset']))
                ->setTime($def['hour'], $def['min'] ?? 0);
            $duration = $doctor->getConsultationDuration();
            $endDate = $apptDate->modify(sprintf('+%d minutes', $duration));

            $appointment = (new Appointment())
                ->setPatient($patient)
                ->setMedecin($doctor)
                ->setDateTime($apptDate)
                ->setEndTime($endDate)
                ->setStatus($status)
                ->setReason($def['reason'])
                ->setAdminNote($def['adminNote'] ?? null);

            if (in_array($status, [AppointmentStatus::CONFIRMED, AppointmentStatus::COMPLETED, AppointmentStatus::REFUSED], true)) {
                $appointment
                    ->setValidatedBy($admin)
                    ->setValidatedAt($apptDate->modify('-1 day'));
            }

            $manager->persist($appointment);

            if ($status === AppointmentStatus::COMPLETED && isset($def['diagnosis'])) {
                $consultation = (new Consultation())
                    ->setAppointment($appointment)
                    ->setDiagnosis($def['diagnosis'])
                    ->setNotes($def['notes'] ?? null)
                    ->setVitalSigns(!empty($def['vitals']) ? $def['vitals'] : null)
                    ->setIsCompleted(true)
                    ->setCompletedAt($apptDate->modify('+' . $duration . ' minutes'));

                $manager->persist($consultation);

                if (!empty($def['rx'])) {
                    $prescription = (new Prescription())
                        ->setConsultation($consultation)
                        ->setNotes('Traitement à débuter immédiatement. Revenir en cas de symptômes persistants ou aggravés.');

                    foreach ($def['rx'] as $order => $rxItem) {
                        $item = (new PrescriptionItem())
                            ->setPrescription($prescription)
                            ->setDosage($rxItem['dosage'])
                            ->setDuration($rxItem['duration'] ?? null)
                            ->setFrequency($rxItem['frequency'] ?? null)
                            ->setInstructions($rxItem['instructions'] ?? null)
                            ->setDisplayOrder($order);

                        if (!empty($rxItem['med']) && isset($medications[$rxItem['med']])) {
                            $item->setMedication($medications[$rxItem['med']]);
                        } elseif (!empty($rxItem['customName'])) {
                            $item->setCustomName($rxItem['customName']);
                        }

                        $prescription->addItem($item);
                        $manager->persist($item);
                    }

                    $manager->persist($prescription);
                }

                $notif = (new Notification())
                    ->setUser($patient)
                    ->setType(NotificationType::CONSULTATION_READY)
                    ->setTitle('Compte-rendu disponible')
                    ->setMessage(sprintf(
                        'Votre compte-rendu de consultation du %s avec %s est disponible.',
                        $apptDate->format('d/m/Y'),
                        $doctor->getDisplayName()
                    ))
                    ->setReferenceId(null);

                $manager->persist($notif);
            }

            if ($status === AppointmentStatus::CONFIRMED) {
                $notif = (new Notification())
                    ->setUser($patient)
                    ->setType(NotificationType::APPOINTMENT_CONFIRMED)
                    ->setTitle('Rendez-vous confirmé')
                    ->setMessage(sprintf(
                        'Votre rendez-vous du %s à %sh avec %s a été confirmé.',
                        $apptDate->format('d/m/Y'),
                        $apptDate->format('H'),
                        $doctor->getDisplayName()
                    ))
                    ->setReferenceId(null);

                $manager->persist($notif);
            }

            if ($status === AppointmentStatus::REFUSED) {
                $notif = (new Notification())
                    ->setUser($patient)
                    ->setType(NotificationType::APPOINTMENT_REFUSED)
                    ->setTitle('Rendez-vous refusé')
                    ->setMessage(sprintf(
                        'Votre demande du %s avec %s n\'a pas pu être acceptée. %s',
                        $apptDate->format('d/m/Y'),
                        $doctor->getDisplayName(),
                        $def['adminNote'] ? 'Motif : ' . $def['adminNote'] : 'Veuillez sélectionner un autre créneau.'
                    ))
                    ->setReferenceId(null);

                $manager->persist($notif);
            }

            if ($status === AppointmentStatus::CONFIRMED && $def['daysOffset'] > 0 && $def['daysOffset'] <= 7) {
                $reminder = (new Notification())
                    ->setUser($patient)
                    ->setType(NotificationType::APPOINTMENT_REMINDER)
                    ->setTitle('Rappel de rendez-vous')
                    ->setMessage(sprintf(
                        'Rappel : vous avez un rendez-vous dans %d jour(s) le %s à %sh avec %s - %s.',
                        $def['daysOffset'],
                        $apptDate->format('d/m/Y'),
                        $apptDate->format('H'),
                        $doctor->getDisplayName(),
                        $doctor->getOfficeLocation() ?? ''
                    ))
                    ->setReferenceId(null);

                $manager->persist($reminder);
            }
        }
    }

    private function createTime(string $time): \DateTimeImmutable
    {
        return new \DateTimeImmutable($time);
    }
}
