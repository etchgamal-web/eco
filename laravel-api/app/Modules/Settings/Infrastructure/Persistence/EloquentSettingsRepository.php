<?php
namespace App\Modules\Settings\Infrastructure\Persistence;
use App\Models\Setting;use App\Modules\Settings\Domain\ValueObjects\SettingData;use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;use Illuminate\Support\Collection;
class EloquentSettingsRepository implements SettingsRepositoryInterface
{
 public function findByKey(string $key):?object{return Setting::query()->where('key',$key)->first();}
 public function getByGroup(string $group):iterable{return Setting::query()->where('group',$group)->orderBy('key')->get();}
 public function getAll():iterable{return Setting::query()->orderBy('group')->orderBy('key')->get();}
 public function save(SettingData $d):object
 {
  $s=Setting::query()->firstOrNew(['key'=>$d->key]);$s->group=$d->group;$s->type=$d->type;$s->description=$d->description;$s->is_secret=$d->isSecret || (bool) ($s->is_secret ?? false);$s->is_encrypted=$d->isEncrypted || (bool) ($s->is_encrypted ?? false);if (!($s->is_secret && $d->value === '********')){$s->setTypedValue($d->value);}$s->save();return $s->refresh();
 }
 public function delete(string $key):bool{return Setting::query()->where('key',$key)->delete()>0;}
}
