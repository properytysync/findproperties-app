type Props = {
  message: string;
};

export default function ErrorMessage({ message }: Props) {
  return (
    <div className="rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-red-300">
      {message}
    </div>
  );
}