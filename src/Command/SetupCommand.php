<?php

namespace App\Command;

use App\Entity\Setting;
use App\Entity\User;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:setup', description: 'Seed default settings and create the first system administrator')]
class SetupCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SettingService $settings,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Default settings
        $defaults = [
            'church_name'      => 'My Church',
            'timezone'         => 'Africa/Nairobi',
            'date_format'      => 'd M Y',
            'locale'           => 'en',
            'session_timeout'  => '30',
            'max_login_fails'  => '5',
            'lockout_minutes'  => '15',
            'retention_months' => '24',
            'mask_contacts'    => '1',
            'mod_giving'       => '1',
            'mod_attendance'   => '1',
            'mod_members'      => '1',
            'mod_reports'      => '1',
        ];
        foreach ($defaults as $key => $value) {
            if (!$this->em->find(Setting::class, $key)) {
                $this->settings->set($key, $value);
            }
        }
        $io->success('Default settings seeded.');

        // Create sysadmin if none exists
        $existing = $this->em->getRepository(User::class)->count(['role' => 'sysadmin']);
        if ($existing === 0) {
            $user = new User();
            $user->setName('System Administrator')
                 ->setUsername('admin')
                 ->setEmail('admin@churchos.local')
                 ->setRole('sysadmin')
                 ->setIsActive(true)
                 ->setPasswordHash($this->hasher->hashPassword($user, 'Admin1234!'));

            $this->em->persist($user);
            $this->em->flush();
            $io->success('Created default sysadmin: username=admin  password=Admin1234!');
            $io->warning('Change the password immediately after first login!');
        } else {
            $io->note('System administrator already exists — skipped.');
        }

        return Command::SUCCESS;
    }
}
