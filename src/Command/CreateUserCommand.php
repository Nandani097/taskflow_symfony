<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// 1. We name the command here. This is what you will type in the terminal!
#[AsCommand(
    name: 'app:create-user',
    description: 'A magical command to create a User directly from the Linux Terminal!',
)]
class CreateUserCommand extends Command
{
    // 2. Dependency Injection: We need the Database and the Password Hasher!
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    // 3. Configure: Tells the Terminal what options are allowed
    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'The name of the new user')
            ->addArgument('email', InputArgument::OPTIONAL, 'The email address of the new user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The plain text password')
        ;
    }

    // 4. Interact: This is the "Conversation" hook. It asks you questions if you forgot to provide arguments!
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle gives us beautiful colors and question prompts
        $io = new SymfonyStyle($input, $output);
        if (!$input->getArgument('username')) {
            $username = $io->ask('🤖 What username should we use for this new user?');
            $input->setArgument('username', $username);
        }
        if (!$input->getArgument('email')) {
            $email = $io->ask('🤖 What email address should we use for this new user?');
            $input->setArgument('email', $email);
        }

        if (!$input->getArgument('password')) {
            $password = $io->askHidden('🤫 What should their password be? (Typing will be hidden for security)');
            $input->setArgument('password', $password);
        }
    }

    // 5. Execute: The actual business logic!
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');

        $io->note('Starting User Creation Process...');

        // Check if the user already exists in the database
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('Wait! A user with the email "%s" already exists in the Database!', $email));
            return Command::FAILURE; // ❌ Returns a failing exit code to Linux
        }

        // Create the user object
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        
        // Hash the password securely
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Save to Database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Output a beautiful green success box
        $io->success(sprintf('Hooray! The user "%s" was successfully created without ever opening the browser!', $email));

        return Command::SUCCESS; // ✅ Returns a successful exit code to Linux
    }
}
