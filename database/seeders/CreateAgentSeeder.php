<?php

namespace Database\Seeders;

use App\Domain\Agent\ValueObjects\AgentType;
use App\Infrastructure\Agent\Models\EloquentAgent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Domain\Agent\ValueObjects\Username;

class CreateAgentSeeder extends Seeder
{
    protected $username_string = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    protected $username_number = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    protected $emails = ['a@a.com', 'b@b.com', 'c@c.com', 'd@d.com', 'e@e.com', 'f@f.com', 'g@g.com', 'h@h.com', 'i@i.com', 'j@j.com', 'k@k.com', 'l@l.com', 'm@m.com', 'n@n.com', 'o@o.com', 'p@p.com', 'q@q.com', 'r@r.com', 's@s.com', 't@t.com', 'u@u.com', 'v@v.com', 'w@w.com', 'x@x.com', 'y@y.com', 'z@z.com'];
    protected $agent_types = ['company', 'super senior', 'senior', 'master', 'agent', 'member'];
    protected $status = ['active', 'inactive'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [];

        // Create 2 companies
        for ($i = 0; $i < 2; $i++) {
            $companies[] = $this->createAgent(
                Username::generateNextUsername(new AgentType(AgentType::COMPANY)),
                $this->emails[$i],
                'Company ' . chr(65 + $i), // A, B
                AgentType::COMPANY,
                null
            );
        }

        // Define agent hierarchy
        $levels = [
            AgentType::SUPER_SENIOR,
            AgentType::SENIOR,
            AgentType::MASTER,
            AgentType::AGENT,
            AgentType::MEMBER,
        ];

        // Create downlines for each company
        foreach ($companies as $index => $company) {
            $currentUpline = $company;
            for ($lvl = 0; $lvl < count($levels); $lvl++) {
                $agentType = $levels[$lvl];
                $emailIndex = ($index * count($levels)) + ($lvl + 2); // offset emails

                // For member, add a phone number that ends with '000' for pattern
                $phone = '12345678' . str_pad((string)($lvl + 2), 2, '0', STR_PAD_LEFT);

                $username = Username::generateNextUsername(new AgentType($agentType), new Username($currentUpline->username));

                $currentUpline = $this->createAgent(
                    $username,
                    $this->emails[$emailIndex] ?? "temp{$index}{$lvl}@temp.com",
                    ucfirst($agentType) . ' ' . $username->value(), // $agent type + username
                    $agentType,
                    $currentUpline->id,
                    $phone
                );
            }
        }
    }

    /**
     * Create an agent with the given parameters
     */
    private function createAgent(
        Username $username,
        string $email,
        string $name,
        string $agentType,
        ?int $uplineId = null,
        string $phone = '1234567890',
        string $password = 'password',
        string $status = 'active'
    ): EloquentAgent {
        return EloquentAgent::create([
            'username' => $username->value(),
            'email' => $email,
            'name' => $name,
            'password' => Hash::make($password),
            'agent_type' => $agentType,
            'status' => $status,
            'upline_id' => $uplineId,
            'phone' => $phone,
            'email_verified_at' => now()->toDateTimeString(),
        ]);
    }
}
