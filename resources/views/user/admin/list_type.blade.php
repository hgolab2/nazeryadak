@foreach($model as $user)
<tr>
    <td>{{ $user->user_id }}</td>
    <td>{{ $user->name }}</td>
    <td>{{ $user->family }}</td>
    <td><code style="font-size:0.8rem;">{{ $user->username }}</code></td>
    <td>{{ $user->role?->title ?? '—' }}</td>
    <td>
        <a href="/admin/user/edit/{{ $user->user_id }}" class="btn btn-sm" style="background:#e3f2fd; color:#1565c0; border:none; font-size:0.75rem;">
            <i class="fas fa-edit"></i> ویرایش
        </a>
    </td>
</tr>
@endforeach
