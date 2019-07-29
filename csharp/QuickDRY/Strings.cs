using System;

namespace QuickDRY
{
    public class Strings
    {
        public static string Currency(int value)
        {
            return value.ToString();
        }

        public static string Currency(float value)
        {
            return value.ToString();
        }

        public static string Currency(double value)
        {
            return value.ToString();
        }

        public static string Currency(string value)
        {
            return value;
        }

        public static string Currency(object value)
        {
            return Currency(Convert.ToDouble(value));
        }

        // https://stackoverflow.com/questions/2706500/how-do-i-generate-a-random-int-number
        private static readonly Random getrandom = new Random();

        public static int GetRandomNumber(int min, int max)
        {
            lock (getrandom) // synchronize
            {
                return getrandom.Next(min, max);
            }
        }

        public static string GUID()
        {
            return string.Format("{0:X}{1:X}-{2:X}-{3:X}-{4:X}-{5:X}{6:X}{7:X}", GetRandomNumber(0, 65535), GetRandomNumber(0, 65535), GetRandomNumber(0, 65535), GetRandomNumber(16384, 20479), GetRandomNumber(32768, 49151), GetRandomNumber(0, 65535), GetRandomNumber(0, 65535), GetRandomNumber(0, 65535));
        }
    }
}
