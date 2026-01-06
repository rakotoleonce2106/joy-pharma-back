<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Creates a new admin user',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'The email of the admin user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The password of the admin user')
            ->addOption('firstName', 'f', InputOption::VALUE_OPTIONAL, 'First name of the admin', 'Admin')
            ->addOption('lastName', 'l', InputOption::VALUE_OPTIONAL, 'Last name of the admin', 'User')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Get email (interactive if not provided)
        $email = $input->getArgument('email');
        if (!$email) {
            $question = new Question('Enter the admin email: ');
            $email = $io->askQuestion($question);
        }

        if (!$email) {
            $io->error('Email is required.');
            return Command::FAILURE;
        }

        // Check if user already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('User with email "%s" already exists.', $email));
            return Command::FAILURE;
        }

        // Get password (interactive if not provided)
        $password = $input->getArgument('password');
        if (!$password) {
            $question = new Question('Enter the admin password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $io->askQuestion($question);
            
            // Confirm password
            $confirmQuestion = new Question('Confirm the password: ');
            $confirmQuestion->setHidden(true);
            $confirmQuestion->setHiddenFallback(false);
            $confirmPassword = $io->askQuestion($confirmQuestion);
            
            if ($password !== $confirmPassword) {
                $io->error('Passwords do not match.');
                return Command::FAILURE;
            }
        }

        if (!$password) {
            $io->error('Password is required.');
            return Command::FAILURE;
        }

        // Get optional name arguments
        $firstName = $input->getOption('firstName');
        $lastName = $input->getOption('lastName');

        // Create the admin user
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_ADMIN']);
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Persist the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Admin user "%s" created successfully!', $email));
        
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['First Name', $firstName],
                ['Last Name', $lastName],
                ['Roles', implode(', ', $user->getRoles())],
            ]
        );

        return Command::SUCCESS;
    }
}
