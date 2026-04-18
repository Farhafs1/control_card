<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">System Audit Trail</h2>
            <p class="text-sm text-slate-500 font-medium">Monitoring all administrative and budgetary actions</p>
        </div>
        <div class="flex space-x-3">
            <select wire:model.live="filterModule" class="rounded-lg border-slate-200 text-sm font-bold text-slate-600">
                <option value="">All Modules</option>
                <option value="Budget">Budget</option>
                <option value="Expenditure">Expenditure</option>
                <option value="User">User Management</option>
                <option value="Auth">Security/Auth</option>
            </select>
            <input type="text" wire:model.live="search" placeholder="Search logs or users..." class="rounded-lg border-slate-200 text-sm px-4">
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 overflow-hidden border border-slate-100">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Timestamp</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">User</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Action</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Module</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($logs as $log)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 text-xs font-bold text-slate-500">{{ $log->created_at->format('M d, H:i:s') }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-7 h-7 bg-emerald-100 rounded-full flex items-center justify-center text-[10px] font-black text-emerald-700">
                                {{ substr($log->user->name, 0, 1) }}
                            </div>
                            <span class="text-xs font-bold text-slate-700">{{ $log->user->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-tighter 
                            {{ $log->action == 'Deleted' ? 'bg-rose-100 text-rose-600' : 'bg-blue-100 text-blue-600' }}">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs font-bold text-slate-600">{{ $log->module }}</td>
                    <td class="px-6 py-4 text-xs text-slate-500 leading-relaxed">{{ $log->description }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-6 bg-slate-50">
            {{ $logs->links() }}
        </div>
    </div>
</div>