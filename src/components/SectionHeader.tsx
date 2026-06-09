export default function SectionHeader({
  eyebrow,
  title,
  right,
}: {
  eyebrow: string;
  title: string;
  right?: React.ReactNode;
}) {
  return (
    <div className="flex items-end justify-between gap-6">
      <div>
        <div className="text-xs font-semibold tracking-wider text-slate-500">{eyebrow}</div>
        <h2 className="mt-2 text-3xl md:text-4xl font-semibold tracking-tight">{title}</h2>
      </div>
      {right ? <div>{right}</div> : null}
    </div>
  );
}