<?php

namespace App\Actions\Fortify;

/* @chisel-teams */
use App\Actions\Teams\CreateTeam;
/* @end-chisel-teams */
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
/* @chisel-teams */
use Illuminate\Support\Facades\DB;
/* @end-chisel-teams */
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /* @chisel-teams */
    public function __construct(private CreateTeam $createTeam)
    {
        //
    }
    /* @end-chisel-teams */

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::query()->create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user;
        });
    }
}
