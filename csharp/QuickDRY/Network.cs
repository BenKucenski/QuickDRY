using System.Net.NetworkInformation;

namespace QuickDRY
{
    public class Network
    {
        public static string GetNetworkIPs()
        {
            // https://stackoverflow.com/questions/9855230/how-do-i-get-the-network-interface-and-its-right-ipv4-address
            string result = "";
            foreach (NetworkInterface ni in NetworkInterface.GetAllNetworkInterfaces())
            {
                if(ni.Name != "Ethernet")
                {
//                    continue;
                }

                if (ni.NetworkInterfaceType == NetworkInterfaceType.Wireless80211 || ni.NetworkInterfaceType == NetworkInterfaceType.Ethernet)
                {
                    result += " " + ni.Name + ": ";
                    foreach (UnicastIPAddressInformation ip in ni.GetIPProperties().UnicastAddresses)
                    {
                        if (ip.Address.AddressFamily == System.Net.Sockets.AddressFamily.InterNetwork)
                        {
                            result += " " + ip.Address.ToString() + " ";
                        }
                    }
                }
            }
            return result.Trim().Replace("  "," ");
        }

    }
}
