make sure clarifier and differentiate, between role and permissions. the differences between the table and view 
permissions -> gives access to data on tables
permisission -> properties, does apprenti own x 

verification the property -> we need to do.

possible more features, 

ocr scanner. we should try.

a system where someone can migrate their excel file -> to the new applications.

with the notes jobtrek.

vertical slicing -> 1 person on 1 tache, db controller view.

mercredi possible. mercredi prochain -> see what's possible see what is not possible.
people with important tasks -> do them
people with extra tasks -> research see what's possible how hard is it to implement how much will it cost. any other stuff.




## Accessing a user's role and apprenticeship

Set at every Microsoft login by `MicrosoftAuthController@callback`, from the user's Azure group (`MappingRolesService::resolveRole`):

| Azure group      | `role`       | `apprenticeship_id` |
|------------------|--------------|---------------------|
| apprentices_IT   | `apprentice` | id of apprenticeship `IT` |
| apprentices_EC   | `apprentice` | id of apprenticeship `EC` |
| trainer          | `trainer`    | `null`              |

### Backend (PHP)

```php
use App\Enums\UserRole;

$user = auth()->user();              // or $request->user()

$user->role;                         // UserRole enum, e.g. UserRole::Apprentice
$user->role->value;                  // 'apprentice' | 'trainer' | ...
$user->role === UserRole::Apprentice // compare against the enum, not a string

$user->apprenticeship_id;            // int|null
$user->apprenticeship?->name;        // 'IT' | 'EC' | null (loads the relation)
```

### Frontend (Vue / Inertia)

The whole user is shared as `auth.user` (`HandleInertiaRequests`), so both fields are already in the page props:

```ts
import { usePage } from '@inertiajs/vue3'

const user = usePage().props.auth.user

user.role              // 'apprentice' | 'trainer'
user.apprenticeship_id // number | null
```

`role` and `apprenticeship_id` are not declared in `resources/js/types/auth.ts` yet: add them to the `User` type so `vue-tsc` accepts them. The apprenticeship name (`IT`/`EC`) is not shared; share it in `HandleInertiaRequests` if the frontend needs it.



